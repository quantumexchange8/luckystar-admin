<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\GroupHasTradingMaster;
use App\Models\TradingMaster;
use App\Models\TradingMasterHasFee;
use App\Models\TradingSubscription;
use App\Models\User;
use App\Services\Data\CreateTradingAccount;
use App\Services\Data\CreateTradingUser;
use App\Services\TradingAccountService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class StrategyController extends Controller
{
    public function index()
    {
        return Inertia::render('Strategy/StrategyListing', [

            'strategiesCount' => TradingMaster::count(),
        ]);
    }

    public function create()
    {
        $account_types = AccountType::where([
            'type' => 'live',
            'status' => 'active',
        ])
            ->get()
            ->toArray();

        return Inertia::render('Strategy/CreateStrategy', [
            'accountTypes' => $account_types,
        ]);
    }

    /**
     * @throws Throwable
     * @throws ConnectionException
     */
    public function addStrategy(Request $request)
    {
        Validator::make($request->all(), [
            'strategy_name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'trader_name' => ['required'],
            'category' => ['required'],
            'strategy_account_type' => ['required'],
            'account_type_id' => [
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('strategy_account_type') == 'create_new' && empty($value)) {
                        return $fail(trans('validation.required', ['attribute' => trans('public.account_type')]));
                    }
                    return 'nullable';
                },
            ],
            'account_number' => [
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('strategy_account_type') == 'existing' && empty($value)) {
                        return $fail(trans('validation.required', ['attribute' => trans('public.account_number')]));
                    }
                    return 'nullable';
                },
            ],
            'estimated_lot' => ['required'],
            'estimated_monthly_return' => ['required'],
            'max_drawdown' => ['required'],
            'cut_loss' => ['required'],
            'additional_capital' => ['nullable', 'numeric'],
            'additional_investors' => ['nullable', 'numeric'],
            'visible_to' => ['required'],
            'description' => ['required'],
            'leverage' => [
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('strategy_account_type') == 'create_new' && empty($value)) {
                        return $fail(trans('validation.required', ['attribute' => trans('public.leverage')]));
                    }
                    return 'nullable';
                },
            ],
            'minimum_investment' => ['required'],
            'investment_period' => ['required'],
            'investment_period_type' => ['required'],
            'settlement_period' => ['required'],
            'settlement_period_type' => ['required'],
            'can_top_up' => ['required'],
            'can_terminate' => ['required'],
            'sharing_profit' => ['required'],
            'market_profit' => ['required'],
            'company_profit' => ['required'],
            'management_fee' => ['nullable'],
        ])->setAttributeNames([
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
            'description' => trans('public.description'),
            'leverage' => trans('public.leverage'),
            'minimum_investment' => trans('public.minimum_investment'),
            'investment_period' => trans('public.investment_period'),
            'investment_period_type' => trans('public.investment_period'),
            'settlement_period' => trans('public.settlement_period'),
            'settlement_period_type' => trans('public.settlement_period'),
            'can_top_up' => trans('public.top_up_strategy'),
            'can_terminate' => trans('public.terminate_strategy'),
            'sharing_profit' => trans('public.profit_distribution'),
            'market_profit' => trans('public.profit_distribution'),
            'company_profit' => trans('public.profit_distribution'),
            'management_fee' => trans('public.management_fee_setting'),
        ])->validate();

        $service = new TradingAccountService();
        $connection = $service->getConnectionStatus();

        if ($connection != 0) {
            return back()->with('toast', [
                'title' => trans('public.connection_error'),
                'message' => trans('public.meta_trader_connection_error'),
                'type' => 'error',
            ]);
        }

        $strategy_account_type = $request->strategy_account_type;
        if ($strategy_account_type == 'existing') {
            $meta_user = $service->getMetaUser($request->account_number);
            if (!$meta_user['login']) {
                throw ValidationException::withMessages(['account_number' => trans('public.account_not_found')]);
            }

            $leverage = $meta_user['leverage'];
            $account_type = AccountType::firstWhere('slug', $meta_user['group']);
        } else {
            $meta_user = [];
            $leverage = $request->leverage['setting_leverage']['value'];
            $account_type = AccountType::find($request->account_type_id);
        }

        $user = User::find(2);

        $master = TradingMaster::create([
            'user_id' => $user->id,
            'master_name' => $request->strategy_name,
            'trader_name' => $request->trader_name,
            'leverage' => $leverage,
            'strategy_account_type' => $strategy_account_type,
            'category' => $request->category,
            'description' => $request->description,
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

        if ($master->strategy_account_type == 'existing') {
            $meta_user['account_type_id'] = $account_type->id;
            (new CreateTradingAccount)->execute($user, $meta_user);
            (new CreateTradingUser)->execute($user, $meta_user);
            $master->account_type_id = $account_type->id;
            $master->meta_login = $meta_user['login'];
        } else {
            $metaAccount = $service->createUser($user, $master->master_name, $account_type, $leverage);
            $master->account_type_id = $account_type->id;
            $master->meta_login = $metaAccount['login'];
        }

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

        return to_route('strategy.listing')->with('toast', [
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
                'user.group.group:id,name,color',
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
