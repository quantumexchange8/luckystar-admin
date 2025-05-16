<?php

namespace App\Services\Data;

use App\Models\AccountType;
use App\Models\TradingUser;
use Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateTradingUser
{
    /**
     * @throws Throwable
     */
    public function execute($meta_login, $data): TradingUser
    {
        return $this->updateTradingUser($meta_login, $data);
    }

    /**
     * @throws Throwable
     */
    public function updateTradingUser($meta_login, $data): TradingUser
    {
        $tradingUser = TradingUser::query()
            ->where('meta_login', $meta_login)
            ->first();

        $accountType = AccountType::firstWhere('name', $data['group']);

        if (Auth::user()->role == 'user') {
            $tradingUser->real_fund = $data['balance'];
        } else {
            $tradingUser->demo_fund = $data['balance'];
        }
        $tradingUser->account_type_id = $accountType->id;
        $tradingUser->name = $data['name'];
        $tradingUser->company = $data['company'];
        $tradingUser->leverage = $data['leverage'];
        $tradingUser->balance = $data['balance'];
        $tradingUser->credit = $data['credit'];
        $tradingUser->rights = $data['rights'];

        if ($data['rights'] == 5) {
            $tradingUser->allow_trade = false;
        } elseif ($data['rights'] == 1) {
            $tradingUser->allow_trade = true;
        }

        DB::transaction(function () use ($tradingUser) {
            $tradingUser->save();
        });

        return $tradingUser;
    }
}
