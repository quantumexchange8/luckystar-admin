<?php

namespace Database\Seeders;

use App\Models\AccountType;
use App\Models\AccountTypeHasLeverage;
use App\Models\SettingLeverage;
use Illuminate\Database\Seeder;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        $account_type = AccountType::create([
            'name' => 'Standard-V',
            'slug' => 'standard-v',
            'category' => 'managed',
            'type' => 'virtual',
            'minimum_deposit' => 100,
            'currency' => 'USD',
            'allow_create_account' => 1,
            'commission_structure' => 'self',
            'trade_open_duration' => '180',
            'maximum_account_number' => 3,
            'color' => '34D399',
            'status' => 'active',
            'allow_trade' => 0,
        ]);

        $leverages = [1, 10, 20, 50, 100, 200, 300, 400, 500];

        foreach ($leverages as $value) {
            SettingLeverage::create([
                'label' => "1:$value",
                'value' => $value,
                'status' => 'active',
            ]);
        }

        $leverageIds = SettingLeverage::pluck('id');

        foreach ($leverageIds as $leverageId) {
            AccountTypeHasLeverage::create([
                'account_type_id' => $account_type->id,
                'setting_leverage_id' => $leverageId,
            ]);
        }
    }
}
