<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('second_password');
            $table->string('security_pin')->nullable();
            $table->string('dial_code', 50)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('phone_number')->nullable()->unique();
            $table->string('chinese_name')->nullable();
            $table->date('dob')->nullable();
            $table->unsignedBigInteger('occupation_id')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('nationality')->nullable();
            $table->longText('address')->nullable();
            $table->string('annual_income')->nullable();
            $table->string('net_worth')->nullable();
            $table->ipAddress('register_ip')->nullable()->default('::1');
            $table->ipAddress('last_login_ip')->nullable()->default('::1');
            $table->unsignedBigInteger('upline_id')->nullable();
            $table->string('hierarchyList')->nullable();
            $table->string('referral_code')->nullable();
            $table->string('id_number')->nullable();
            $table->string('gender')->nullable();
            $table->string('role')->default('user');
            $table->unsignedBigInteger('setting_rank_id')->default(1);
            $table->unsignedBigInteger('display_rank_id')->default(1);
            $table->string('rank_up_status')->default('auto');
            $table->string('status')->default('active');
            $table->string('employment_status')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
