<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\Subscription;
use DB;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;
use Inertia\Inertia;
use App\Enums\MetaService;
use App\Models\AccountType;
use App\Models\Kyc;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingSubscription;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use App\Services\RunningNumberService;
use App\Services\TradingAccountService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PendingController extends Controller
{
    public function pending_investment()
    {
        return Inertia::render('Pending/Investment/PendingInvestment');
    }

    public function getPendingInvestmentsData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Subscriber::with([
                'user',
                'user.group.group:id,name,color',
                'trading_master',
                'trading_master.account_type',
            ])
                ->where('status', 'pending');

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->whereRaw("CONCAT(`first_name`, ' ', `last_name`) LIKE ?", ['%' . $keyword . '%'])
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('username', 'like', '%' . $keyword . '%');
                        });
                    })->orWhere('meta_login', 'like', '%' . $keyword . '%')
                        ->orWhere('master_meta_login', 'like', '%' . $keyword . '%');
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if (!empty($data['filters']['group_id']['value'])) {
                $query->whereHas('user.group', function ($q) use ($data) {
                    $q->where('group_id', $data['filters']['group_id']['value']['id']);
                });
            }

            if (!empty($data['filters']['status']['value'])) {
                $query->where('status', $data['filters']['status']['value']);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            $transactions = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    /**
     * @throws Throwable
     * @throws ConnectionException
     */
    public function pendingInvestmentApproval(Request $request)
    {

        Validator::make($request->all(), [
            'subscriber_id' => ['required'],
            'action' => ['required'],
            'remarks' => ['nullable', 'required_if:action,reject'],
        ])->setAttributeNames([
            'subscriber_id' => trans('public.investment'),
            'action' => trans('public.action'),
            'remarks' => trans('public.remarks'),
        ])->validate();

        $subscriber = Subscriber::find($request->subscriber_id);

        if ($subscriber->status == 'pending') {
            switch ($request->action) {
                case 'approve':
                    $subscriber->status = 'subscribing';
                    $subscriber->completed_at = Carbon::now()->add($subscriber->subscription_period, $subscriber->subscription_period_unit)->endOfDay();
                    $subscriber->settlement_start_at = Carbon::now();
                    $subscriber->settlement_end_at = Carbon::now()
                        ->add($subscriber->settlement_period, $subscriber->settlement_period_unit)
                        ->subDay()
                        ->endOfDay();

                    DB::beginTransaction();

                    try {
                        $master = $subscriber->trading_master->trading_account;
                        $master_account_type = AccountType::where([
                            'type' => 'live',
                            'account_group' => $subscriber->master_account_type,
                        ])->first();

                        $maxAttempts = 5;
                        $attempts = 0;

                        do {
                            try {
                                Subscription::create([
                                    'subscriber_id'        => $subscriber->id,
                                    'subscription_amount'  => $subscriber->initial_amount,
                                    'real_fund'            => $subscriber->user->role == 'user' ? $subscriber->initial_amount : 0,
                                    'demo_fund'            => $subscriber->user->role != 'user' ? $subscriber->initial_amount : 0,
                                    'subscription_number'  => RunningNumberService::getID('subscription'),
                                ]);

                                $success = true;

                            } catch (QueryException $e) {
                                if ($e->getCode() === '23000') { // Duplicate
                                    $attempts++;
                                    $success = false;
                                } else {
                                    throw $e;
                                }
                            }
                        } while (!$success && $attempts < $maxAttempts);

                        if (!$success) {
                            DB::rollBack();
                            return back()->with('toast', [
                                'title' => trans('public.invalid_action'),
                                'message' => trans('public.try_error', ['attempts_count' => $maxAttempts]),
                                'type' => 'error',
                            ]);
                        }

                        // Attempt to create deal
                        $deal = (new TradingAccountService())->createDeal(
                            $master,
                            $master->master_name,
                            $subscriber->initial_amount,
                            "Login #$subscriber->meta_login",
                            MetaService::DEPOSIT,
                            $master_account_type,
                            MetaService::DEAL_BALANCE
                        );

                        if (!$deal || !isset($deal['deal_Id'])) {
                            // Fail: rollback the subscription
                            DB::rollBack();

                            return back()->with('toast', [
                                'title' => trans('public.invalid_action'),
                                'message' => 'Failed to create deal, please try again.',
                                'type' => 'error',
                            ]);
                        }

                        // All good — create transaction record
                        Transaction::create([
                            'user_id' => $master->user_id,
                            'category' => 'trading_account',
                            'transaction_type' => 'invest_capital',
                            'to_meta_login' => $master->meta_login,
                            'ticket' => $deal['deal_Id'],
                            'transaction_number' => RunningNumberService::getID('transaction'),
                            'amount' => $subscriber->initial_amount,
                            'from_currency' => 'USD',
                            'to_currency' => 'USD',
                            'conversion_rate' => 1,
                            'conversion_amount' => $subscriber->initial_amount,
                            'transaction_amount' => $subscriber->initial_amount,
                            'status' => 'success',
                            'comment' => $deal['conduct_Deal']['comment'] ?? 'Deposit',
                            'approval_at' => now(),
                        ]);

                        DB::commit();

                    } catch (Throwable $e) {
                        DB::rollBack();
                        Log::error('Error during subscription + deal creation: ' . $e->getMessage());

                        return back()->with('toast', [
                            'title' => trans('public.invalid_action'),
                            'message' => 'An error occurred while processing, please try again.',
                            'type' => 'error',
                        ]);
                    }
                    break;

                case 'reject':
                    $subscriber->status = 'rejected';
                    $subscriber->remarks = $request->remarks;

                    break;

                default:
                    break;
            }

            $subscriber->approval_at = Carbon::now();
            $subscriber->save();
        } else {
            return back()->with('toast', [
                'title' => trans('public.invalid_action'),
                'message' => trans('public.toast_investment_not_pending_warning'),
                'type' => 'warning',
            ]);
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_investment_approval_success'),
            'type' => 'success',
        ]);
    }

    public function pending_kyc()
    {
        return Inertia::render('Member/Kyc/PendingKyc');
    }

    public function getPendingKycRequest(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Kyc::with([
                'user:id,first_name,last_name,email,username,id_number,identity_number,address',
                'user.group.group',
                'media',
            ])
                ->where('kyc_status', 'pending');

            if ($data['filters']['global']) {
                $keyword = $data['filters']['global'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->whereRaw("CONCAT(`first_name`, ' ', `last_name`) LIKE ?", ['%' . $keyword . '%'])
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('username', 'like', '%' . $keyword . '%')
                                ->orWhere('id_number', 'like', '%' . $keyword . '%');
                        });
                    });
                });
            }

            if (!empty($data['filters']['start_date']) && !empty($data['filters']['end_date'])) {
                $start_date = Carbon::parse($data['filters']['start_date'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            if (!empty($data['filters']['group_id'])) {
                $query->whereHas('user.group', function ($q) use ($data) {
                    $q->where('group_id', $data['filters']['group_id']);
                });
            }

            if (!empty($data['filters']['category'])) {
                $query->where('category', $data['filters']['category']);
            }

            if (!empty($data['filters']['proof_type'])) {
                $query->where('proof_type', $data['filters']['proof_type']);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            $kycs = $query->paginate($data['rows']);

            foreach ($kycs as $kyc) {
                if ($kyc->relationLoaded('user') && $kyc->user) {
                    $kyc->full_name = $kyc->user->full_name ?? null;
                    $kyc->first_name = $kyc->user->first_name ?? null;
                    $kyc->last_name = $kyc->user->last_name ?? null;
                    $kyc->email = $kyc->user->email ?? null;
                    $kyc->username = $kyc->user->username ?? null;
                    $kyc->id_number = $kyc->user->id_number ?? null;
                    $kyc->identity_number = $kyc->user->identity_number ?? null;
                    $kyc->address = $kyc->user->address ?? null;

                    if ($kyc->user->relationLoaded('group') && $kyc->user->group && $kyc->user->group->relationLoaded('group') && $kyc->user->group->group) {
                        $group = $kyc->user->group->group;
                        $kyc->group_id = $group->id ?? null;
                        $kyc->group_name = $group->name ?? null;
                        $kyc->group_leader_id = $group->group_leader_id ?? null;
                        $kyc->group_color = $group->color ?? null;
                        $kyc->parent_group_id = $group->parent_group_id ?? null;
                    }
                }

                unset($kyc->user);
            }

            return response()->json([
                'success' => true,
                'data' => $kycs,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function pendingKycApproval(Request $request)
    {
        Validator::make($request->all(), [
            'kyc_id' => ['required'],
            'action' => ['required'],
            'remarks' => ['nullable', 'required_if:action,reject'],
        ])->setAttributeNames([
            'kyc_id' => trans('public.kyc'),
            'action' => trans('public.action'),
            'remarks' => trans('public.remarks'),
        ])->validate();

        $kycRequest = Kyc::find($request->kyc_id);

        if ($kycRequest && $kycRequest->kyc_status == 'pending') {
            if ($request->action === 'approve') {
                $kycRequest->kyc_status = 'verified';
            } elseif ($request->action === 'reject') {
                $kycRequest->kyc_status = 'unverified';
            }

            $kycRequest->kyc_approval_at = Carbon::now();

            if ($request->remarks) {
                $kycRequest->kyc_approval_description = $request->remarks;
            }

            $kycRequest->save();
        } else {
            return back()->with('toast', [
                'title' => trans('public.invalid_action'),
                'message' => trans('public.toast_kyc_not_pending_warning'),
                'type' => 'warning',
            ]);
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_kyc_approval_success'),
            'type' => 'success',
        ]);
    }

    public function pending_withdrawal()
    {
        return Inertia::render('Pending/Withdrawal/PendingWithdrawal');
    }

    public function getPendingWithdrawalsData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = Transaction::query()
                ->with([
                    'user:id,first_name,last_name,email,upline_id',
                ])
                ->where('transaction_type', 'withdrawal')
                ->where('status', 'pending');

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('user', function ($query) use ($keyword) {
                        $query->where(function ($q) use ($keyword) {
                            $q->whereRaw("CONCAT(`first_name`, ' ', `last_name`) LIKE ?", ['%' . $keyword . '%'])
                                ->orWhere('email', 'like', '%' . $keyword . '%')
                                ->orWhere('username', 'like', '%' . $keyword . '%');
                        });
                    })->orWhere('transaction_number', 'like', '%' . $keyword . '%');
                });
            }

            //date filter
            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay(); //add day to ensure capture entire day
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->latest();
            }

            $withdrawals = $query->paginate($data['rows']);

            $totalWithdrawalAmount = (clone $query)
                ->sum('amount');

            $pendingWithdrawalCounts = (clone $query)
                ->count();

            return response()->json([
                'success' => true,
                'data' => $withdrawals,
                'totalWithdrawalAmount' => $totalWithdrawalAmount,
                'pendingWithdrawalCounts' => $pendingWithdrawalCounts,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function pendingWithdrawalApproval(Request $request)
    {
        Validator::make($request->all(), [
            'transaction_id' => ['required'],
            'action' => ['required'],
        ])->setAttributeNames([
            'transaction_id' => trans('public.investment'),
            'action' => trans('public.action'),
        ])->validate();

        $transaction = Transaction::find($request->transaction_id);
        $wallet = Wallet::find($transaction->from_wallet_id);

        if ($transaction->status == 'pending') {
            switch ($request->action) {
                case 'approve':
                    $transaction->status = 'success';
                    break;

                case 'reject':
                    if (!$request->remarks) {
                        throw ValidationException::withMessages(['remarks' => trans('public.remarks_required_reject')]);
                    }
                    $transaction->status = 'rejected';
                    $transaction->remarks = $request->remarks;

                    $wallet->balance += $transaction->amount;
                    $wallet->save();
                    break;

                default:
                    break;
            }

            $transaction->approval_at = Carbon::now();
            $transaction->handle_by = Auth::id();
            $transaction->save();
        } else {
            return back()->with('toast', [
                'title' => trans('public.invalid_action'),
                'message' => trans('public.toast_withdrawal_not_pending_warning'),
                'type' => 'warning',
            ]);
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_withdrawal_approval_success'),
            'type' => 'success',
        ]);
    }
}
