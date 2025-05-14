<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\AccountType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\SettingLeverage;
use App\Models\AccountTypeHasLeverage;
use Illuminate\Support\Facades\Validator;

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

    public function getAccountTypes()
    {
        $accountTypes = AccountType::withCount('trading_accounts')
            ->with('account_leverages')
            ->get();
    
        foreach ($accountTypes as $accountType) {
            $accountType->trade_delay = $accountType->trade_open_duration >= 60
                ? ($accountType->trade_open_duration / 60) . ' min'
                : $accountType->trade_open_duration . ' sec';
    
            $accountType->total_account = $accountType->trading_accounts_count;
    
            unset($accountType->trading_accounts_count);
    
            $accountType->leverages = $accountType->account_leverages->pluck('setting_leverage_id')->values();
            unset($accountType->account_leverages);
        }
    
        return response()->json(['accountTypes' => $accountTypes]);
    }

    public function updateAccountType(Request $request)
    {
        Validator::make($request->all(), [
            'name' => ['required'],
            'category' => ['required'],
            'type' => ['required'],
            'minimum_deposit' => ['required', 'numeric'],
            'leverages' => ['required'],
            'allow_trade' => ['required'],
            'maximum_account_number' => ['required', 'numeric'],
            'color' => ['required'],
        ])->setAttributeNames([
            'name' => trans('public.name'),
            'category' => trans('public.category'),
            'type' => trans('public.type'),
            'minimum_deposit' => trans('public.minimum_deposit'),
            'leverages' => trans('public.leverage'),
            'allow_trade' => trans('public.allow_trade'),
            'maximum_account_number' => trans('public.maximum_account'),
            'color' => trans('public.color'),
        ])->validate();

        $account_type = AccountType::findOrFail($request->id);
    
        // Update AccountType fields
        $account_type->update([
            'category' => $request->category,
            'type' => $request->type,
            'minimum_deposit' => $request->minimum_deposit,
            'allow_trade' => $request->allow_trade,
            'maximum_account_number' => $request->maximum_account_number,
            'color' => $request->color,
        ]);
    
        // Handle user access updates
        $leverages = $request->leverages ?? [];
    
        if ($leverages) {
            $existingLeverageIds = AccountTypeHasLeverage::where('account_type_id', $request->id)
                ->pluck('setting_leverage_id')
                ->toArray();
    
            $incomingLeverageIds = collect($leverages)->toArray();
    
            if (!empty(array_diff($existingLeverageIds, $incomingLeverageIds)) || !empty(array_diff($incomingLeverageIds, $existingLeverageIds))) {
                AccountTypeHasLeverage::where('account_type_id', $request->id)->delete();
    
                foreach ($leverages as $leverage) {
                    AccountTypeHasLeverage::create([
                        'account_type_id' => $account_type->id,
                        'setting_leverage_id' => $leverage,
                    ]);
                }
            }
        } else {
            AccountTypeHasLeverage::where('account_type_id', $request->id)->delete();
        }
    
        return back()->with('toast', [
            'title' => trans('public.toast_update_account_type_success'),
            'type' => 'success',
        ]);
    }

    public function updateStatus($id)
    {
        $account_type = AccountType::find($id);
        $account_type->status = $account_type->status == 'active' ? 'inactive' : 'active';
        $account_type->save();

        return back()->with('toast', [
            'title' => $account_type->status == 'active' ? trans("public.toast_account_type_activated") : trans("public.toast_account_type_deactivated"),
            'type' => 'success',
        ]);
    }

}
