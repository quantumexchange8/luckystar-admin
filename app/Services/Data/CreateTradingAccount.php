<?php

namespace App\Services\Data;

use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateTradingAccount
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, $data): TradingAccount
    {
        return $this->storeNewAccount($user, $data);
    }

    /**
     * @throws Throwable
     */
    public function storeNewAccount(User $user, $data): TradingAccount
    {
        $tradingAccount = new TradingAccount();
        $tradingAccount->user_id = $user->id;
        $tradingAccount->meta_login = $data['login'];
        $tradingAccount->account_type_id = $data['account_type_id'];
        $tradingAccount->margin_leverage = $data['leverage'];

        DB::transaction(function () use ($tradingAccount) {
            $tradingAccount->save();
        });

        return $tradingAccount;
    }
}
