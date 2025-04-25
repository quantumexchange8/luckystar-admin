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
        Schema::create('trading_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('meta_login')->unique();
            $table->unsignedBigInteger('account_type_id')->default(1);
            $table->string('name')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_password')->nullable();
            $table->unsignedInteger('leverage')->nullable();
            $table->integer('cert_serial_number')->nullable();
            $table->integer('rights')->nullable();
            $table->string('registration')->nullable();
            $table->timestamp('last_access')->nullable();
            $table->timestamp('last_pass_change')->nullable();
            $table->ipAddress('last_login_at')->nullable();
            $table->string('company')->nullable();
            $table->integer('language')->nullable();
            $table->integer('client_id')->nullable();
            $table->string('meta_id')->nullable();
            $table->string('status')->nullable();
            $table->string('comment')->nullable();
            $table->unsignedInteger('color')->nullable();
            $table->integer('agent')->nullable();
            $table->decimal('balance', 13)->nullable()->default(0);
            $table->decimal('real_fund', 13)->nullable()->default(0);
            $table->decimal('demo_fund', 13)->nullable()->default(0);
            $table->decimal('credit', 13)->nullable()->default(0);
            $table->decimal('interest_rate', 11)->nullable();
            $table->decimal('commission_daily', 11)->nullable();
            $table->decimal('commission_monthly', 11)->nullable();
            $table->float('balance_prev_day')->nullable();
            $table->float('balance_prev_month')->nullable();
            $table->float('equity_prev_day')->nullable();
            $table->float('equity_prev_month')->nullable();
            $table->string('trade_accounts')->nullable(); //User account numbers in external trading systems as [gateway ID]=[account number in the system to which the gateway is connected].

            //extra
            $table->string('trade_accounts_currency')->nullable();
            $table->string('trade_accounts_platform')->nullable();
            $table->string('trade_accounts_type')->nullable();

            $table->string('lead_campaign')->nullable();
            $table->string('lead_source')->nullable();

            //additional
            $table->string('remarks')->nullable();
            $table->boolean('allow_trade')->default(true);
            $table->boolean('allow_change_pass')->default(true);
            $table->string('module')->default('trading');
            $table->string('category')->nullable();
            $table->string('acc_status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('account_type_id')
                ->references('id')
                ->on('account_types')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trading_users');
    }
};
