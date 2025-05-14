<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupHasUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $group = Group::create([
            'name' => 'Lucky Star',
            'group_leader_id' => 2,
            'color' => '34D399',
            'edited_by' => 1
        ]);

        GroupHasUser::create([
            'group_id' => $group->id,
            'user_id' => 2,
        ]);

        DB::table('group_rank_settings')->insert([
            [
                'id' => 1,
                'group_id' => $group->id,
                'rank_name' => 'member',
                'rank_code' => 'MB',
                'rank_position' => 1,
                'lot_rebate_currency' => 'USD',
                'lot_rebate_amount' => 0,
                'min_direct_referral' => null,
                'min_direct_referral_rank_id' => null,
                'group_sales_currency' => null,
                'max_capped_per_line' => 0,
                'min_group_sales' => 0,
                'edited_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'group_id' => $group->id,
                'rank_name' => 'junior_introducing_broker',
                'rank_code' => 'JIB',
                'rank_position' => 2,
                'lot_rebate_currency' => 'USD',
                'lot_rebate_amount' => 3,
                'min_direct_referral' => 2,
                'min_direct_referral_rank_id' => 1,
                'group_sales_currency' => 'USD',
                'max_capped_per_line' => 5000,
                'min_group_sales' => 10000,
                'edited_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'group_id' => $group->id,
                'rank_name' => 'senior_introducing_broker',
                'rank_code' => 'SIB',
                'rank_position' => 3,
                'lot_rebate_currency' => 'USD',
                'lot_rebate_amount' => 6,
                'min_direct_referral' => 2,
                'min_direct_referral_rank_id' => 2,
                'group_sales_currency' => 'USD',
                'max_capped_per_line' => 25000,
                'min_group_sales' => 50000,
                'edited_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 4,
                'group_id' => $group->id,
                'rank_name' => 'chief_introducing_broker',
                'rank_code' => 'CIB',
                'rank_position' => 4,
                'lot_rebate_currency' => 'USD',
                'lot_rebate_amount' => 9,
                'min_direct_referral' => 2,
                'min_direct_referral_rank_id' => 3,
                'group_sales_currency' => 'USD',
                'max_capped_per_line' => 125000,
                'min_group_sales' => 250000,
                'edited_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 5,
                'group_id' => $group->id,
                'rank_name' => 'advanced_broker_specialist',
                'rank_code' => 'ABS',
                'rank_position' => 5,
                'lot_rebate_currency' => 'USD',
                'lot_rebate_amount' => 12,
                'min_direct_referral' => 3,
                'min_direct_referral_rank_id' => 4,
                'group_sales_currency' => 'USD',
                'max_capped_per_line' => 625000,
                'min_group_sales' => 1250000,
                'edited_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 6,
                'group_id' => $group->id,
                'rank_name' => 'industry_master_broker',
                'rank_code' => 'IMB',
                'rank_position' => 6,
                'lot_rebate_currency' => 'USD',
                'lot_rebate_amount' => 15,
                'min_direct_referral' => 3,
                'min_direct_referral_rank_id' => 5,
                'group_sales_currency' => 'USD',
                'max_capped_per_line' => 2500000,
                'min_group_sales' => 5000000,
                'edited_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }
}
