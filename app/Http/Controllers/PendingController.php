<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Enums\MetaService;
use App\Models\AccountType;
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
                            $q->where('first_name', 'like', '%' . $keyword . '%')
                                ->orWhere('last_name', 'like', '%' . $keyword . '%')
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
                        'from_meta_login' => $investment->meta_login,
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
}
