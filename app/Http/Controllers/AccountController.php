<?php

namespace App\Http\Controllers;

use App\Enums\MetaService;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\RunningNumberService;
use App\Services\TradingAccountService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function getTradingAccountData(Request $request)
    {
        try {
            $account = TradingAccount::with('account_type')->where('meta_login', $request->meta_login)->first();
    
            if (!$account) {
                return response()->json([
                    'message' => 'Account not found.',
                ], 404);
            }
    
            // Check if account type is virtual
            if ($account->account_type && $account->account_type->type !== 'virtual') {
                // Only fetch external info if not virtual
                (new TradingAccountService)->getUserInfo((int) $request->meta_login);
    
                // Refresh to get updated values
                $account = TradingAccount::where('meta_login', $request->meta_login)->first();
            }
    
            return response()->json([
                'currentAmount' => [
                    'account_balance' => $account->balance,
                    'account_credit' => $account->credit,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error("Error updating account {$request->meta_login}: {$e->getMessage()}");
    
            return response()->json([
                'message' => 'An error occurred while fetching account data.',
            ], 500);
        }
    }
        
    public function accountAdjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'remarks' => ['nullable'],
        ])->setAttributeNames([
            'action' => trans('public.action'),
            'amount' => trans('public.amount'),
            'remarks' => trans('public.remarks'),
        ]);
        $validator->validate();

        $trading_account = TradingAccount::with('account_type')->where('meta_login', $request->meta_login)->first();
        $accountType = AccountType::find($trading_account->account_type_id);
        $action = $request->action;
        $type = $request->type;
        $amount = $request->amount;

        // Check if editing is locked due to active or pending subscriptions
        if ($trading_account->has_active_or_pending_subscriptions()) {
            return back()->with('toast', [
                'title' => trans('public.toast_account_modification_blocked'),
                'type' => 'warning',
            ]);
        }

        if ($accountType->type !== 'virtual') {
            try {
                // Fetch and update user info using TradingAccountService
                (new TradingAccountService)->getUserInfo((int) $request->meta_login);
        
                // Retrieve the updated account data
                $trading_account = TradingAccount::with('account_type')->where('meta_login', $request->meta_login)->first();
        
                if (!$trading_account) {
                    return back()->with('toast', [
                            'title' => trans('public.no_account_found'),
                            'type' => 'error'
                        ]);
                }
            } catch (\Throwable $e) {
                // Log any errors during the process
                Log::error("Error updating account {$request->meta_login}: {$e->getMessage()}");
        
                return back()
                        ->with('toast', [
                            'title' => trans('public.no_account_found'),
                            'type' => 'error'
                        ]);
            }
        }
        
        if ($type === 'account_balance' && $action === 'balance_out' && $trading_account->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        if ($type === 'account_credit' && $action === 'credit_out' && $trading_account->credit < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_credit')]);
        }

        $transaction = Transaction::create([
            'user_id' => $trading_account->user_id,
            'category' => 'trading_account',
            'transaction_type' => $action,
            'from_meta_login' => ($action === 'balance_out' || $action === 'credit_out') ? $trading_account->meta_login : null,
            'to_meta_login' => ($action === 'balance_in' || $action === 'credit_in') ? $trading_account->meta_login : null,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'amount' => $amount,
            'transaction_amount' => $amount,
            'status' => 'processing',
            'remarks' => $request->remarks,
            'handle_by' => Auth::id(),
        ]);

        $changeType = match($action) {
            'balance_in', 'credit_in' => MetaService::DEPOSIT,
            'balance_out', 'credit_out' => MetaService::WITHDRAW,
            default => throw ValidationException::withMessages(['action' => trans('public.invalid_type')]),
        };
        
        $dealType = match($type) {
            'account_balance' => MetaService::DEAL_BALANCE,
            'account_credit' => MetaService::DEAL_CREDIT,
            default => throw ValidationException::withMessages(['type' => trans('public.invalid_type')]),
        };
                
        
        if (($action === 'balance_out' || $action === 'credit_out')) {
            $amount = -abs($amount);
        }

        try {
            $deal = (new TradingAccountService())->createDeal($trading_account, $amount, $transaction->remarks, $changeType, $trading_account->account_type, $dealType);

            $transaction->update([
                'ticket' => $deal['deal_Id'],
                'approval_at' => now(),
                'status' => 'success',
            ]);

            return redirect()->back()->with('toast', [
                'title' => $type == 'account_balance' ? trans('public.toast_balance_adjustment_success') : trans('public.toast_credit_adjustment_success'),
                'type' => 'success'
            ]);
        } catch (\Throwable $e) {
            // Update transaction status to failed on error
            $transaction->update([
                'approval_at' => now(),
                'status' => 'failed'
            ]);

            // Log the main error
            Log::error('Error creating deal: ' . $e->getMessage());

            // Attempt to get the account and mark account as inactive if not found
            $account = (new TradingAccountService())->getMetaUser($trading_account->meta_login);
            if (!$account) {
                TradingUser::where('meta_login', $trading_account->meta_login)
                    ->update(['acc_status' => 'inactive']);
            }

            return back()->with('toast', [
                    'title' => trans('public.toast_adjustment_error'),
                    'type' => 'error'
                ]);
        }
    }

    public function updateLeverage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meta_login' => ['required'],
            'leverage' => ['required'],
        ])->setAttributeNames([
            'meta_login' => trans('public.meta_login'),
            'leverage' => trans('public.leverage'),
        ]);
        $validator->validate();

        $tradingAccount = TradingAccount::where('meta_login', $request->meta_login)->first();
        $accountType = AccountType::find($tradingAccount->account_type_id);
        
        // Check if editing is locked due to active or pending subscriptions
        if ($tradingAccount->has_active_or_pending_subscriptions()) {
            return back()->with('toast', [
                'title' => trans('public.toast_account_modification_blocked'),
                'type' => 'warning',
            ]);
        }

        try {
            (new TradingAccountService())->updateLeverage($tradingAccount, $request->leverage, $accountType);

            return redirect()->back()->with('toast', [
                'title' => trans('public.toast_change_leverage_success'),
                'type' => 'success'
            ]);
        } catch (\Throwable $e) {
            // Log the main error
            Log::error('Error updating leverage: ' . $e->getMessage());

            return back()
                ->with('toast', [
                    'title' => trans('public.toast_change_leverage_failed'),
                    'type' => 'error'
                ]);
        }
    }

    // public function updateAccountGroup(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'meta_login' => ['required'],
    //         'account_group' => ['required'],
    //     ])->setAttributeNames([
    //         'meta_login' => trans('public.meta_login'),
    //         'account_group' => trans('public.account_type'),
    //     ]);
    //     $validator->validate();

    //     try {
    //         (new TradingAccountService())->updateAccountGroup($request->meta_login, $request->account_group);

    //         return redirect()->back()->with('toast', [
    //             'title' => trans('public.toast_change_account_type_success'),
    //             'type' => 'success'
    //         ]);
    //     } catch (\Throwable $e) {
    //         // Log the main error
    //         Log::error('Error update account group: ' . $e->getMessage());

    //         return back()
    //             ->with('toast', [
    //                 'title' => trans('public.toast_change_account_type_failed'),
    //                 'type' => 'error'
    //             ]);
    //     }
    // }

    public function change_password(Request $request)
    {
        Validator::make($request->all(), [
            'meta_login' => ['required'],
            'master_password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()->symbols(), 'required_without:investor_password'],
            'investor_password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()->symbols(), 'required_without:master_password'],
        ])->setAttributeNames([
            'meta_login' => trans('public.meta_login'),
            'master_password' => trans('public.master_password'),
            'investor_password' => trans('public.investor_password'),
        ])->validate();

        // $user = User::find($request->user_id);
        $meta_login = $request->meta_login;
        $master_password = $request->master_password;
        $investor_password = $request->investor_password;
        $tradingAccount = TradingAccount::where('meta_login', $request->meta_login)->first();
        $accountType = AccountType::find($tradingAccount->account_type_id);
        
        // Check if editing is locked due to active or pending subscriptions
        if ($tradingAccount->has_active_or_pending_subscriptions()) {
            return back()->with('toast', [
                'title' => trans('public.toast_account_modification_blocked'),
                'type' => 'warning',
            ]);
        }

        // Try to update passwords and send notification
        try {
            if ($master_password || $investor_password) {
                if ($master_password) {
                    (new TradingAccountService())->changePassword($meta_login, MetaService::MAIN_PASSWORD, $master_password);
                }

                if ($investor_password) {
                    (new TradingAccountService())->changePassword($meta_login, MetaService::INVESTOR_PASSWORD, $investor_password);
                }

                // // Send notification
                // Notification::route('mail', $user->email)
                //     ->notify(new ChangeTradingAccountPasswordNotification($user, $meta_login, $master_password, $investor_password));
            }

            // Success response
            return back()->with('toast', [
                'title' => trans("public.toast_update_password_success"),
                'type' => 'success',
            ]);

        } catch (\Throwable $e) {
            // Log the error
            Log::error('Error updating trading account password: ' . $e->getMessage());

            // Failure response
            return back()->with('toast', [
                'title' => trans('public.toast_update_password_failed'),
                'type' => 'error',
            ]);
        }
    }

    public function accountDelete(Request $request)
    {
        $trading_account = TradingAccount::where('meta_login', $request->meta_login)->first();
        $accountType = AccountType::find($trading_account->account_type_id);
        
        // Check if editing is locked due to active or pending subscriptions
        if ($trading_account->has_active_or_pending_subscriptions()) {
            return back()->with('toast', [
                'title' => trans('public.toast_account_modification_blocked'),
                'type' => 'warning',
            ]);
        }

        if ($accountType->type !== 'virtual') {
            try {
                // Fetch and update user info using TradingAccountService
                (new TradingAccountService)->getUserInfo((int) $request->meta_login);
        
                // Retrieve the updated account data
                $trading_account = TradingAccount::with('account_type')->where('meta_login', $request->meta_login)->first();
        
                if (!$trading_account) {
                    return back()->with('toast', [
                            'title' => trans('public.no_account_found'),
                            'type' => 'error'
                        ]);
                }
            } catch (\Throwable $e) {
                // Log any errors during the process
                Log::error("Error updating account {$request->meta_login}: {$e->getMessage()}");
        
                return back()
                        ->with('toast', [
                            'title' => trans('public.no_account_found'),
                            'type' => 'error'
                        ]);
            }
        }

        if ($trading_account->balance > 0 || $trading_account->equity > 0 || $trading_account->credit > 0) {
            return back()
                ->with('toast', [
                    'title' => trans('public.account_have_balance'),
                    'type' => 'error'
                ]);
        }

        // try {
        //     $cTraderService->deleteTrader($trading_account->meta_login);

        //     $trading_account->trading_user->delete();
        //     $trading_account->delete();

        //     // Return success response with a flag for toast
        //     return redirect()->back()->with('toast', [
        //         'title' => trans('public.toast_delete_trading_account_success'),
        //         'type' => 'success',
        //     ]);
        // } catch (\Throwable $e) {
        //     // Log the error and return failure response
        //     Log::error('Failed to delete trading account: ' . $e->getMessage());

        //     return back()
        //         ->with('toast', [
        //             'title' => 'No Account Found',
        //             'type' => 'error'
        //         ]);
        // }
    }
}
