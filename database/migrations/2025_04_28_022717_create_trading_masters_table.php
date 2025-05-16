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
        Schema::create('trading_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('master_name')->nullable();
            $table->string('trader_name')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->unsignedBigInteger('account_type_id')->nullable();
            $table->integer('leverage')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->decimal('sharing_profit')->nullable();
            $table->decimal('market_profit')->nullable();
            $table->decimal('company_profit')->nullable();
            $table->decimal('minimum_investment', 13)->nullable();
            $table->decimal('subscription_fee')->nullable()->default(0);
            $table->string('estimated_lot')->nullable();
            $table->string('estimated_monthly_return')->nullable();
            $table->string('max_drawdown')->nullable();
            $table->string('cut_loss')->nullable();
            $table->decimal('additional_capital', 13)->nullable()->default(0);
            $table->integer('additional_investors')->nullable()->default(0);
            $table->integer('investment_period')->nullable();
            $table->string('investment_period_type')->nullable();
            $table->integer('settlement_period')->nullable();
            $table->string('settlement_period_type')->nullable();
            $table->tinyInteger('signal_status')->nullable()->default(1);
            $table->tinyInteger('can_top_up')->nullable()->default(1);
            $table->tinyInteger('can_terminate')->nullable()->default(1);
            $table->string('status')->default('active');
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
        Schema::dropIfExists('trading_masters');
    }
};
