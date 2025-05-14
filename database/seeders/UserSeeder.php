<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Lucky Star',
            'last_name' => 'Admin',
            'username' => 'luckystaradmin',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' =>  Hash::make('testtest'),
            'remember_token' => Str::random(10),
            'dial_code' => '60',
            'phone' => '123456789',
            'phone_number' => '60123456789',
            'identity_number' => '123456121234',
            'country_id' => 132,
            'nationality' => 'Malaysian',
            'referral_code' => 'LSx666666',
            'id_number' => 'SID000001',
            'role' => 'super_admin',
        ]);

        User::create([
            'first_name' => 'Lucky Star',
            'last_name' => 'Company',
            'username' => 'luckystar',
            'email' => 'company@luckystar.com',
            'email_verified_at' => now(),
            'password' =>  Hash::make('Lucky1234.'),
            'remember_token' => Str::random(10),
            'dial_code' => '60',
            'phone' => '112233445',
            'phone_number' => '60112233445',
            'identity_number' => '123456121234',
            'country_id' => 132,
            'nationality' => 'Malaysian',
            'referral_code' => 'LSx123456',
            'id_number' => 'LID000002',
            'role' => 'user',
        ]);
    }
}
