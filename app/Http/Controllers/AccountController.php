<?php

namespace App\Http\Controllers;

use App\Enums\MetaService;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\RunningNumberService;
use App\Services\TradingAccountService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Throwable;

class AccountController extends Controller
{
    public function getTradingAccountData(Request $request)
    {
        try {
            if ($request->type != 'virtual') {
                $service = new TradingAccountService();

                if ($service->getConnectionStatus() != 0) {
                    return back()->with('toast', [
                        'title' => trans("public.connection_error"),
                        'message' => trans("public.toast_connection_error"),
                        'type' => 'error',
                    ]);
                }

                $service->getUserInfo($request->meta_login);
            }

            $account = TradingAccount::firstWhere('meta_login', $request->meta_login);

            return response()->json([
                'data' => $account
            ]);
        } catch (Throwable $e) {
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

        if ($accountType->type != 'virtual') {
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
            } catch (Throwable $e) {
                // Log any errors during the process
                Log::error("Error updating account {$request->meta_login}: {$e->getMessage()}");

                return back()
                        ->with('toast', [
                            'title' => trans('public.no_account_found'),
                            'type' => 'error'
                        ]);
            }
        }

        if ($type == 'account_balance' && $action == 'balance_out' && $trading_account->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.account_balance_is_insufficient')]);
        }

        if ($type === 'account_credit' && $action === 'credit_out' && $trading_account->credit < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.account_credit_is_insufficient')]);
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
            $trading_user = $trading_account->trading_user;
            $deal = (new TradingAccountService())->createDeal($trading_account, $trading_user->name, $amount, $transaction->remarks, $changeType, $trading_account->account_type, $dealType);

            $transaction->update([
                'ticket' => $deal['deal_Id'],
                'approval_at' => now(),
                'status' => 'success',
            ]);

            return redirect()->back()->with('toast', [
                'title' => $type == 'account_balance' ? trans('public.toast_balance_adjustment_success') : trans('public.toast_credit_adjustment_success'),
                'type' => 'success'
            ]);
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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

        } catch (Throwable $e) {
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
            } catch (Throwable $e) {
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

    public function account_listing()
    {
        return Inertia::render('Account/AccountListing');
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function getAccountsData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = TradingAccount::with([
                'user:id,first_name,last_name,email,username',
                'user.media',
                'user.upline',
                'user.group.group:id,name,group_leader_id,color',
                'account_type',
                'active_subscriptions',
                'active_subscriptions.trading_master',
                'trading_master:meta_login',
            ])->withSum('active_subscriptions', 'subscription_amount');

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->where('first_name', 'like', '%' . $keyword . '%')
                                ->orWhere('last_name', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('username', 'like', '%' . $keyword . '%');
                        });
                    })->orWhere('meta_login', 'like', '%' . $keyword . '%');
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('updated_at', [$start_date, $end_date]);
            }

            if (!empty($data['filters']['group_id']['value'])) {
                $query->whereHas('user.group', function ($q) use ($data) {
                    $q->where('group_id', $data['filters']['group_id']['value']['id']);
                });
            }

            if (!empty($data['filters']['account_type']['value'])) {
                $query->where('account_type_id', $data['filters']['account_type']['value']['id']);
            }

            if (!empty($data['filters']['referrers']['value'])) {
                $query->whereHas('user', function ($q) use ($data) {
                    $selected_referrers = $data['filters']['referrers']['value'];
                    $userIds = array_column($selected_referrers, 'user_id');

                    $q->whereIn('upline_id', $userIds);
                });
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('updated_at')
                    ->orderByDesc('id');
            }

//            if ($request->has('exportStatus')) {
//                return Excel::download(new InvestmentExport($query, $status), now() . '-investment-report.xlsx');
//            }

            $accounts = $query->paginate($data['rows']);

            $accounts->getCollection()->transform(function ($account) {
                $account->has_active_or_pending_subscriptions = $account->has_active_or_pending_subscriptions();
                return $account;
            });

            return response()->json([
                'success' => true,
                'data' => $accounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function getAccountTransaction(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);
            $meta_login = $data['filters']['meta_login']['value'];

            $query = Transaction::where([
                'category' => 'trading_account',
                'status' => 'success',
            ])->where(function ($q) use ($meta_login) {
                $q->where('from_meta_login', $meta_login)
                    ->orWhere('to_meta_login', $meta_login);
            });

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if (!empty($data['filters']['types']['value'])) {
                $query->whereIn('transaction_type', $data['filters']['types']['value']);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('updated_at')
                    ->orderByDesc('id');
            }

//            if ($request->has('exportStatus')) {
//                return Excel::download(new InvestmentExport($query, $status), now() . '-investment-report.xlsx');
//            }

            $accounts = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $accounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
