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
            $table->unsignedInteger('meta_login')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->decimal('min_investment', 13)->nullable();
            $table->decimal('sharing_profit')->nullable();
            $table->decimal('sa_profit')->nullable();
            $table->decimal('market_profit')->nullable();
            $table->string('estimated_monthly_returns')->nullable();
            $table->string('estimated_lot_size')->nullable();
            $table->decimal('subscription_fee')->nullable()->default(0);
            $table->decimal('extra_fund')->nullable()->default(0);
            $table->decimal('total_fund')->nullable()->default(0);
            $table->string('max_drawdown')->nullable();
            $table->string('settlement_period_type')->nullable();
            $table->integer('settlement_period')->nullable();
            $table->string('join_period_type')->nullable();
            $table->integer('join_period')->nullable();
            $table->tinyInteger('signal_status')->nullable()->default(1);
            $table->tinyInteger('can_top_up')->nullable()->default(1);
            $table->tinyInteger('can_revoke')->nullable()->default(1);
            $table->string('visibility_type')->nullable();
            $table->string('status')->nullable()->default('active');
            $table->unsignedBigInteger('handle_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('handle_by')
                ->references('id')
                ->on('users')
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
