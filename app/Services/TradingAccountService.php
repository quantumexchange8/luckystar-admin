<?php

namespace App\Services;

use Throwable;
use App\Enums\MetaService;
use App\Models\User as UserModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Services\Data\CreateTradingUser;
use App\Services\Data\UpdateTradingUser;
use App\Services\Data\CreateTradingAccount;
use App\Services\Data\UpdateTradingAccount;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Client\ConnectionException;

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
            return Http::acceptJson()->timeout(10)
                ->get($this->baseURL . "/connect_status")
                ->json();
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

    /**
     * @throws Throwable
     */
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
    public function createDeal($trading_account, $name, $amount, $comment, $type, $account_type, $deal_type)
    {
        if ($account_type->type == 'virtual') {
            $dealResponse = [
                'deal_Id' => null,
                'conduct_Deal' => [
                    'comment' => $comment
                ]
            ];

            $newBalance = $trading_account->balance;
            $newCredit = $trading_account->credit ?? 0;

            switch ($deal_type) {
                case MetaService::DEAL_BALANCE:
                    $newBalance += $amount;
                    break;

                case MetaService::DEAL_CREDIT:
                    $newCredit += $amount;
                    break;

                case MetaService::DEAL_BONUS:
                    // if bonus affects balance or credit, modify as needed
                    break;

                default:
                    throw ValidationException::withMessages(['deal_type' => trans('public.invalid_type')]);
            }

            $userData = [
                'group' => $account_type->name,
                'name' => $name,
                'company' => null,
                'leverage' => $trading_account->margin_leverage,
                'balance' => $newBalance,
                'credit' => $newCredit,
                'rights' => 5
            ];

            $metaAccountData = [
                'balance' => $newBalance,
                'credit' => $newCredit,
                'currencyDigits' => 2,
                'marginLeverage' => $trading_account->margin_leverage,
                'equity' => $trading_account->equity + $amount,
                'floating' => $trading_account->floating,
            ];
        } else {
            $dealResponse = Http::acceptJson()->post($this->baseURL . "/conduct_deal", [
                'login' => $trading_account->meta_login,
                'amount' => abs($amount),
                'imtDeal_EnDealAction' => $deal_type,
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

    public function updateLeverage($trading_account, $leverage, $account_type)
    {
        if ($account_type->type == 'virtual') {
            $updatedResponse = [
                'login' => $trading_account->meta_login,
                'leverage' => $leverage,
            ];

            $userData = [
                'group' => $account_type->name,
                'name' => Auth::user()->full_name,
                'company' => null,
                'leverage' => $leverage,
                'balance' => $trading_account->balance,
                'credit' => $trading_account->credit ?? 0,
                'rights' => 5
            ];

            $metaAccountData = [
                'balance' => $trading_account->balance,
                'currencyDigits' => 2,
                'credit' => $trading_account->credit ?? 0,
                'marginLeverage' => $leverage,
                'equity' => $trading_account->equity,
                'floating' => $trading_account->floating,
            ];

        } else {
            $updatedResponse = Http::acceptJson()->patch($this->baseURL . "/update_leverage", [
                'login' => $trading_account->meta_login,
                'leverage' => $leverage,
            ]);
            $updatedResponse = $updatedResponse->json();
            $userData = $this->getMetaUser($trading_account->meta_login);
            $metaAccountData = $this->getMetaAccount($trading_account->meta_login);
        }
        (new UpdateTradingAccount)->execute($trading_account->meta_login, $metaAccountData);
        (new UpdateTradingUser)->execute($trading_account->meta_login, $userData);

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
