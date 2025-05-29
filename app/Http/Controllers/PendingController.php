<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Enums\MetaService;
use App\Models\AccountType;
use App\Models\Kyc;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingSubscription;
use Illuminate\Support\Facades\Auth;
use App\Services\RunningNumberService;
use App\Services\TradingAccountService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\ConnectionException;

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

            $query = TradingSubscription::with([
                'user',
                'user.upline',
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

            if (!empty($data['filters']['referrers']['value'])) {
                $query->whereHas('user', function ($q) use ($data) {
                    $selected_referrers = $data['filters']['referrers']['value'];
                    $userIds = array_column($selected_referrers, 'user_id');

                    $q->whereIn('upline_id', $userIds);
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
            'investment_id' => ['required'],
            'action' => ['required'],
            'remarks' => ['nullable', 'required_if:action,reject'],
        ])->setAttributeNames([
            'investment_id' => trans('public.investment'),
            'action' => trans('public.action'),
            'remarks' => trans('public.remarks'),
        ])->validate();

        $investment = TradingSubscription::find($request->investment_id);

        if ($investment->status == 'pending') {
            switch ($request->action) {
                case 'approve':
                    $investment->status = 'active';
                    $investment->expired_at = Carbon::now()->add($investment->subscription_period, $investment->subscription_period_type)->endOfDay();
                    $investment->settlement_at = Carbon::now()
                        ->add($investment->settlement_period, $investment->settlement_period_type)
                        ->addDay()
                        ->startOfDay();

                    $master = $investment->trading_master;
                    $master_account_type = AccountType::find($master->account_type_id);

                    $deal = (new TradingAccountService())->createDeal($master, $investment->subscription_amount, "Login #$investment->meta_login", MetaService::DEPOSIT, $master_account_type, MetaService::DEAL_BALANCE);

                    Transaction::create([
                        'user_id' => $master->user_id,
                        'category' => 'trading_account',
                        'transaction_type' => 'invest_capital',
                        'to_meta_login' => $master->meta_login,
                        'ticket' => $deal['deal_Id'] ?? null,
                        'transaction_number' => RunningNumberService::getID('transaction'),
                        'amount' => $investment->subscription_amount,
                        'from_currency' => 'USD',
                        'to_currency' => 'USD',
                        'conversion_rate' => 1,
                        'conversion_amount' => $investment->subscription_amount,
                        'transaction_amount' => $investment->subscription_amount,
                        'fund_type' => $investment->real_fund > 0 ? MetaService::REAL_FUND : MetaService::DEMO_FUND,
                        'status' => 'success',
                        'comment' => $deal['conduct_Deal']['comment'] ?? 'Deposit',
                        'approval_at' => now(),
                    ]);

                    break;

                case 'reject':
                    $investment->status = 'rejected';
                    $investment->remarks = $request->remarks;

                    break;

                default:
                    break;
            }

            $investment->approval_at = Carbon::now();
            $investment->handle_by = Auth::id();
            $investment->save();
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

}
