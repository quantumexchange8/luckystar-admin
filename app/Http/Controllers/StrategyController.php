<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\GroupHasTradingMaster;
use App\Models\TradingMaster;
use App\Models\TradingMasterHasFee;
use App\Models\User;
use App\Services\TradingAccountService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Throwable;

class StrategyController extends Controller
{
    public function index()
    {
        $account_types = AccountType::where('status', 'active')
            ->get()
            ->toArray();

        return Inertia::render('Strategy/StrategyListing', [
            'accountTypes' => $account_types
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
            'master_name' => $request->master_name,
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
}
