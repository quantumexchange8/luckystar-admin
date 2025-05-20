<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\GroupHasTradingMaster;
use App\Models\TradingMaster;
use App\Models\TradingMasterHasFee;
use App\Models\TradingSubscription;
use App\Models\User;
use App\Services\TradingAccountService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Throwable;

class StrategyController extends Controller
{
    public function index()
    {
        // TODO:: Get LIVE account type
        $account_types = AccountType::where('status', 'active')
            ->get()
            ->toArray();

        return Inertia::render('Strategy/StrategyListing', [
            'accountTypes' => $account_types,
            'strategiesCount' => TradingMaster::count(),
        ]);
    }

    /**
     * @throws Throwable
     * @throws ConnectionException
     */
    public function addStrategy(Request $request)
    {
        $form_step = $request->step;

        $rules = [
            'strategy_name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'trader_name' => ['required'],
            'category' => ['required'],
            'account_type_id' => ['required'],
            'estimated_lot' => ['required'],
            'estimated_monthly_return' => ['required'],
            'max_drawdown' => ['required'],
            'cut_loss' => ['required'],
            'additional_capital' => ['nullable', 'numeric'],
            'additional_investors' => ['nullable', 'numeric'],
            'visible_to' => ['required'],
            'leverage' => ['required'],
        ];

        $attributeNames = [
            'strategy_name' => trans('public.strategy_name'),
            'trader_name' => trans('public.trader_name'),
            'category' => trans('public.category'),
            'account_type_id' => trans('public.account_type'),
            'estimated_lot' => trans('public.estimated_lot'),
            'estimated_monthly_return' => trans('public.estimated_monthly_return'),
            'max_drawdown' => trans('public.max_drawdown'),
            'cut_loss' => trans('public.cut_loss'),
            'additional_capital' => trans('public.additional_capital'),
            'additional_investors' => trans('public.additional_investors'),
            'visible_to' => trans('public.visible_to'),
            'leverage' => trans('public.leverage'),
        ];

        switch ($form_step) {
            case 1:
                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();

                return back();

            case 2:
                $rules['minimum_investment'] = ['required'];
                $rules['investment_period'] = ['required'];
                $rules['investment_period_type'] = ['required'];
                $rules['settlement_period'] = ['required'];
                $rules['settlement_period_type'] = ['required'];
                $rules['can_top_up'] = ['required'];
                $rules['can_terminate'] = ['required'];
                $rules['sharing_profit'] = ['required'];
                $rules['market_profit'] = ['required'];
                $rules['company_profit'] = ['required'];

                $attributeNames['minimum_investment'] = trans('public.minimum_investment');
                $attributeNames['investment_period'] = trans('public.investment_period');
                $attributeNames['investment_period_type'] = trans('public.investment_period');
                $attributeNames['settlement_period'] = trans('public.settlement_period');
                $attributeNames['settlement_period_type'] = trans('public.settlement_period');
                $attributeNames['can_top_up'] = trans('public.top_up_strategy');
                $attributeNames['can_terminate'] = trans('public.terminate_strategy');
                $attributeNames['sharing_profit'] = trans('public.profit_distribution');
                $attributeNames['market_profit'] = trans('public.profit_distribution');
                $attributeNames['company_profit'] = trans('public.profit_distribution');

                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();
                return back();

            default:
                $rules['management_fee'] = ['nullable'];
                $attributeNames['management_fee'] = trans('public.management_fee_setting');

                Validator::make($request->all(), $rules)
                    ->setAttributeNames($attributeNames)
                    ->validate();
                break;
        }

        $user = User::find(2);
        $leverage = $request->leverage['setting_leverage']['value'];

        $master = TradingMaster::create([
            'user_id' => $user['id'],
            'master_name' => $request->strategy_name,
            'trader_name' => $request->trader_name,
            'leverage' => $leverage,
            'category' => $request->category,
            'estimated_lot' => $request->estimated_lot,
            'estimated_monthly_return' => $request->estimated_monthly_return,
            'max_drawdown' => $request->max_drawdown,
            'cut_loss' => $request->cut_loss,
            'additional_capital' => $request->additional_capital ?? 0,
            'additional_investors' => $request->additional_investors ?? 0,
            'minimum_investment' => $request->minimum_investment,
            'sharing_profit' => $request->sharing_profit,
            'market_profit' => $request->market_profit,
            'company_profit' => $request->company_profit,
            'investment_period' => $request->investment_period,
            'investment_period_type' => $request->investment_period_type,
            'settlement_period' => $request->settlement_period,
            'settlement_period_type' => $request->settlement_period_type,
        ]);

        $service = new TradingAccountService();
        $connection = $service->getConnectionStatus();

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans('public.connection_error'),
                'message' => trans('public.meta_trader_connection_error'),
                'type' => 'error',
            ]);
        }

        $account_type = AccountType::find($request->account_type_id);
        $metaAccount = $service->createUser($user, $account_type, $leverage);
        $master->account_type_id = $account_type->id;
        $master->leverage = $leverage;
        $master->meta_login = $metaAccount['login'];
        $master->save();

        $groups = $request->visible_to;

        foreach ($groups as $group) {
            GroupHasTradingMaster::create([
                'group_id' => $group['id'],
                'trading_master_id' => $master->id,
            ]);
        }

        $managementFees = $request->input('management_fee');

        if ($managementFees) {
            foreach ($managementFees as $fee) {
                TradingMasterHasFee::create([
                    'trading_master_id' => $master->id,
                    'meta_login' => $master->meta_login,
                    'management_days' => $fee['days'],
                    'management_percentage' => $fee['percentage'],
                ]);
            }
        }

        return back()->with('toast', [
            'title' => trans('public.success'),
            'message' => trans('public.toast_create_strategy_success'),
            'type' => 'success',
        ]);
    }

    public function getStrategiesOverview()
    {
        // current month
        $endOfMonth = \Illuminate\Support\Carbon::now()->endOfMonth();

        // last month
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $subscription_query = TradingSubscription::where('status', 'active');

        // current month assets
        $current_month_assets = (clone $subscription_query)
            ->whereDate('approval_at', '<=', $endOfMonth)
            ->sum('subscription_amount');

        // current month investors
        $current_month_investors = (clone $subscription_query)
            ->whereDate('approval_at', '<=', $endOfMonth)
            ->count();

        // last month assets
        $last_month_assets = (clone $subscription_query)
            ->whereDate('approval_at', '<=', $endOfLastMonth)
            ->sum('subscription_amount');

        // last month investors
        $last_month_investors = (clone $subscription_query)
            ->whereDate('approval_at', '<=', $endOfLastMonth)
            ->count();

        // comparison % of assets vs last month
        $last_month_asset_comparison = $last_month_assets > 0
            ? (($current_month_assets - $last_month_assets) / $last_month_assets) * 100
            : ($current_month_assets > 0 ? 100 : 0);

        // comparison % of investors vs last month
        $last_month_investor_comparison = $current_month_investors - $last_month_investors;

        // Get and format top 3 masters by total fund
        $topThreeMaster = TradingSubscription::select(
            'master_meta_login',
            DB::raw('SUM(subscription_amount) as total_investment')
        )
            ->where('status', 'active')
            ->groupBy('master_meta_login')
            ->orderByDesc('total_investment')
            ->take(3)
            ->with(['trading_master:meta_login,master_name'])
            ->get();

        return response()->json([
            'currentAssets' => $current_month_assets,
            'lastMonthAssetComparison' => $last_month_asset_comparison,
            'currentInvestors' => $current_month_investors,
            'lastMonthInvestorComparison' => $last_month_investor_comparison,
            'topThreeMaster' => $topThreeMaster,
        ]);
    }

    public function getStrategyData(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $query = TradingMaster::with([
                'account_type',
                'groups',
            ])
                ->withCount('active_subscriptions')
                ->withCount('completed_subscriptions')
                ->withCount('revoked_subscriptions')
                ->withSum('active_subscriptions', 'real_fund');

            if ($data['filters']['global']['value']) {
                $keyword = $data['filters']['global']['value'];

                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhereHas('group_leader', function ($query) use ($keyword) {
                            $query->where('first_name', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('email', 'LIKE', '%' . $keyword . '%');
                        });
                });
            }

            if (!empty($data['filters']['start_date']['value']) && !empty($data['filters']['end_date']['value'])) {
                $start_date = Carbon::parse($data['filters']['start_date']['value'])->addDay()->startOfDay();
                $end_date = Carbon::parse($data['filters']['end_date']['value'])->addDay()->endOfDay();

                $query->whereBetween('created_at', [$start_date, $end_date]);
            }

            //sort field/order
            if ($data['sortField'] && $data['sortOrder']) {
                $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                $query->orderBy($data['sortField'], $order);
            } else {
                $query->orderByDesc('created_at');
            }

            $groups = $query->paginate($data['rows']);

            foreach ($groups as $group) {
                $group->group_names = $group->groups->pluck('name')->join(', ');
            }

            return response()->json([
                'success' => true,
                'data' => $groups,
                'totalWalletTopUp' => (float) $groups->sum('wallet_top_up'),
                'totalWalletWithdrawal' => (float) $groups->sum('wallet_withdrawal'),
                'totalActiveCapital' => (float) $groups->sum('active_capital'),
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }

    public function getInvestmentDataByStrategy(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $status = $data['filters']['status']['value'];
            $strategy = $data['filters']['strategy']['value'];

            $query = TradingSubscription::with([
                'user',
                'user.upline',
                'user.group.group:id,name,color',
                'trading_master',
                'trading_master.account_type',
            ])
                ->where('status', $status)
                ->where('master_meta_login', $strategy);

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

                if ($status == 'revoked') {
                    $query->whereBetween('terminated_at', [$start_date, $end_date]);
                } else {
                    $query->whereBetween('approval_at', [$start_date, $end_date]);
                }
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

                if ($data['sortField'] == 'days') {
                    $query->orderBy('approval_at', $data['sortOrder'] == 0 ? 'asc' : 'desc');
                } else {
                    $query->orderBy($data['sortField'], $order);
                }
            } else {
                if ($status == 'revoked') {
                    $query->orderByDesc('terminated_at');
                } else {
                    $query->orderByDesc('approval_at');
                }
            }

            $transactions = $query->paginate($data['rows']);

            return response()->json([
                'success' => true,
                'data' => $transactions,
            ]);
        }

        return response()->json(['success' => false, 'data' => []]);
    }
}
