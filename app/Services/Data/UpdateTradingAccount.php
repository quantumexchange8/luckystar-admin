<?php

namespace App\Services\Data;

use App\Models\TradingAccount;
use Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateTradingAccount
{
    /**
     * @throws Throwable
     */
    public function execute($meta_login, $data): TradingAccount
    {
        return $this->updateTradingAccount($meta_login, $data);
    }

    /**
     * @throws Throwable
     */
    public function updateTradingAccount($meta_login, $data): TradingAccount
    {
        $tradingAccount = TradingAccount::with('trading_user')
            ->where('meta_login', $meta_login)
            ->first();

        if (Auth::user()->role == 'user') {
            $tradingAccount->real_fund = $data['balance'];
        } else {
            $tradingAccount->demo_fund = $data['balance'];
        }
        $tradingAccount->account_type_id = $tradingAccount->trading_user->account_type_id;;
        $tradingAccount->currency_digits = $data['currencyDigits'];
        $tradingAccount->balance = $data['balance'];
        $tradingAccount->credit = $data['credit'];
        $tradingAccount->margin_leverage = $data['marginLeverage'];
        $tradingAccount->equity = $data['equity'];
        $tradingAccount->floating = $data['floating'];

        DB::transaction(function () use ($tradingAccount) {
            $tradingAccount->save();
        });

        return $tradingAccount;
    }
}
