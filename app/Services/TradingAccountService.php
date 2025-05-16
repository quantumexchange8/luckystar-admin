<?php

namespace App\Services;

use App\Enums\MetaService;
use App\Models\User as UserModel;
use App\Services\Data\CreateTradingAccount;
use App\Services\Data\CreateTradingUser;
use App\Services\Data\UpdateTradingAccount;
use App\Services\Data\UpdateTradingUser;
use Auth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TradingAccountService {
    private string $port = "8443";
    private string $login = "10012";
    private string $password = "Test1234.";
    //private string $baseURL = "http://192.168.0.224:5000/api";
    private string $baseURL = "http://43.231.4.154:5000/api";

    private string $token = "6f0d6f97-3042-4389-9655-9bc321f3fc1e";
    private string $environmentName = "live";

    public function getConnectionStatus()
    {
        try {
            return 0;
        } catch (ConnectionException $exception) {
            // Handle the connection timeout error
            // For example, returning an empty array as a default response
            return [];
        }
    }

    public function getMetaUser($meta_login)
    {
        return Http::acceptJson()->get($this->baseURL . "/m_user/{$meta_login}")->json();
    }

    public function getMetaAccount($meta_login)
    {
        return Http::acceptJson()->get($this->baseURL . "/trade_acc/{$meta_login}")->json();
    }

    public function getUserInfo($meta_login): void
    {
        $userData = $this->getMetaUser($meta_login);
        $metaAccountData = $this->getMetaAccount($meta_login);
        if($userData && $metaAccountData) {
            (new UpdateTradingAccount)->execute($meta_login, $metaAccountData);
            (new UpdateTradingUser)->execute($meta_login, $userData);
        }
    }

    /**
     * @throws ConnectionException
     * @throws Throwable
     */
    public function createUser(UserModel $user, $account_type, $leverage)
    {
        if ($account_type->type == 'virtual') {
            $data = [
                'name' => $user->full_name,
                'login' => RunningNumberService::getID('virtual_account'),
                'leverage' => $leverage,
                'account_type_id' => $account_type->id
            ];
        } else {
            $accountResponse = Http::acceptJson()->post($this->baseURL . "/create_user", [
                'name' => $user->full_name,
                'group' => $account_type->name,
                'leverage' => $leverage,
                'eMail' => $user->email,
            ]);
            $data = $accountResponse->json();

            if (!$account_type->allow_trade) {
                $this->disableTrade($data['login']);
            }
        }

        (new CreateTradingAccount)->execute($user, $data);
        (new CreateTradingUser)->execute($user, $data);

        return $data;
    }

    /**
     * @throws ConnectionException
     * @throws Throwable
     */
    public function createDeal($trading_account, $amount, $comment, $type, $account_type)
    {
        if ($account_type->type == 'virtual') {
            $dealResponse = [
                'deal_Id' => null,
                'conduct_Deal' => [
                    'comment' => $comment
                ]
            ];

            $userData = [
                'group' => $account_type->name,
                'name' => Auth::user()->full_name,
                'company' => null,
                'leverage' => $trading_account->margin_leverage,
                'balance' => $trading_account->balance + $amount,
                'credit' => $trading_account->credit ?? 0,
                'rights' => 5
            ];

            $metaAccountData = [
                'balance' => $trading_account->balance + $amount,
                'currencyDigits' => 2,
                'credit' => $trading_account->credit ?? 0,
                'marginLeverage' => $trading_account->margin_leverage,
                'equity' => $trading_account->equity + $amount,
                'floating' => $trading_account->floating,
            ];
        } else {
            $dealResponse = Http::acceptJson()->post($this->baseURL . "/conduct_deal", [
                'login' => $trading_account->meta_login,
                'amount' => $amount,
                'imtDeal_EnDealAction' => MetaService::DEAL_BALANCE,
                'comment' => $comment,
                'deposit' => $type,
            ]);

            $dealResponse = $dealResponse->json();
            $userData = $this->getMetaUser($trading_account->meta_login);
            $metaAccountData = $this->getMetaAccount($trading_account->meta_login);
        }

        (new UpdateTradingAccount)->execute($trading_account->meta_login, $metaAccountData);
        (new UpdateTradingUser)->execute($trading_account->meta_login, $userData);
        return $dealResponse;
    }

    public function disableTrade($meta_login)
    {
        $disableTrade = Http::acceptJson()->patch($this->baseURL . "/disable_trade/$meta_login")->json();

        $userData = $this->getMetaUser($meta_login);
        $metaAccountData = $this->getMetaAccount($meta_login);
        (new UpdateTradingAccount)->execute($meta_login, $metaAccountData);
        (new UpdateTradingUser)->execute($meta_login, $userData);

        return $disableTrade;
    }

    public function dealHistory($meta_login, $start_date, $end_date)
    {
        return Http::acceptJson()->get($this->baseURL . "/deal_history/{$meta_login}&{$start_date}&{$end_date}")->json();
    }

    public function updateLeverage($meta_login, $leverage)
    {
        $updatedResponse = Http::acceptJson()->patch($this->baseURL . "/update_leverage", [
            'login' => $meta_login,
            'leverage' => $leverage,
        ]);
        $updatedResponse = $updatedResponse->json();
        $userData = $this->getMetaUser($meta_login);
        $metaAccountData = $this->getMetaAccount($meta_login);
        (new UpdateTradingAccount)->execute($meta_login, $metaAccountData);
        (new UpdateTradingUser)->execute($meta_login, $userData);

        return $updatedResponse;
    }

    public function changePassword($meta_login, $type, $password)
    {
        $passwordResponse = Http::acceptJson()->patch($this->baseURL . "/change_password", [
            'login' => $meta_login,
            'type' => $type,
            'password' => $password,
        ]);
        return $passwordResponse->json();
    }

    public function userTrade($meta_login)
    {
        return Http::acceptJson()->get($this->baseURL . "/check_position/{$meta_login}")->json();
    }

}
