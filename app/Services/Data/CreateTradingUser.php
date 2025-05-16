<?php

namespace App\Services\Data;

use App\Models\TradingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateTradingUser
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, $data): TradingUser
    {
        return $this->storeNewUser($user, $data);
    }

    /**
     * @throws Throwable
     */
    public function storeNewUser(User $user, $data): TradingUser
    {
        $tradingUser = new TradingUser();
        $tradingUser->user_id = $user->id;
        $tradingUser->name = $data['name'];
        $tradingUser->meta_login = $data['login'];
        $tradingUser->account_type_id = $data['account_type_id'];
        $tradingUser->leverage = $data['leverage'];

        DB::transaction(function () use ($tradingUser) {
            $tradingUser->save();
        });

        return $tradingUser;
    }
}
