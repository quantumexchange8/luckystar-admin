<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use App\Models\AccountTypeHasLeverage;
use App\Models\SettingLeverage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Str;

class AccountTypeController extends Controller
{
    public function index()
    {
        $leverages = SettingLeverage::where('status', 'active')
            ->select([
                'id',
                'label',
                'value',
                'status'
            ])
            ->get()
            ->toArray();

        return Inertia::render('AccountType/AccountSetting', [
            'leverageOptions' => $leverages,
        ]);
    }

    public function addAccountType(Request $request)
    {
        Validator::make($request->all(), [
            'name' => ['required'],
            'category' => ['required'],
            'type' => ['required'],
            'minimum_deposit' => ['required', 'numeric'],
            'leverages' => ['required'],
            'allow_trade' => ['required'],
            'color' => ['required'],
            'maximum_account_number' => ['required', 'numeric'],
        ])->setAttributeNames([
            'name' => trans('public.name'),
            'category' => trans('public.category'),
            'type' => trans('public.type'),
            'minimum_deposit' => trans('public.minimum_deposit'),
            'leverages' => trans('public.leverage'),
            'allow_trade' => trans('public.allow_trade'),
            'color' => trans('public.color'),
            'maximum_account_number' => trans('public.maximum_account'),
        ])->validate();

        $account_type = AccountType::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'category' => $request->category,
            'type' => $request->type,
            'minimum_deposit' => $request->minimum_deposit,
            'currency' => 'USD',
            'allow_create_account' => 1,
            'commission_structure' => 'self',
            'trade_open_duration' => '180',
            'maximum_account_number' => $request->maximum_account_number,
            'color' => $request->color,
            'allow_trade' => $request->allow_trade,
        ]);

        $leverages = $request->leverages;

        foreach ($leverages as  $leverage) {
            AccountTypeHasLeverage::create([
                'account_type_id' => $account_type->id,
                'setting_leverage_id' => $leverage['id'],
            ]);
        }

        return back()->with('toast', [
            'title' => trans("public.success"),
            'message' => trans("public.toast_add_account_type_success"),
            'type' => 'success',
        ]);
    }
}
