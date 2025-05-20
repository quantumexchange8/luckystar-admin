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
        Schema::create('trading_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->unsignedInteger('master_meta_login')->nullable();
            $table->string('type')->nullable();
            $table->decimal('subscription_amount', 13)->nullable()->default(0);
            $table->decimal('real_fund', 13)->nullable()->default(0);
            $table->decimal('demo_fund', 13)->nullable()->default(0);
            $table->string('subscription_number')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->string('subscription_period_type')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->integer('settlement_period')->nullable();
            $table->string('settlement_period_type')->nullable();
            $table->timestamp('settlement_at')->nullable();
            $table->timestamp('approval_at')->nullable();
            $table->string('status')->nullable()->default('active');
            $table->timestamp('terminated_at')->nullable();
            $table->text('remarks')->nullable();
            $table->string('extra_conditions')->nullable();
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
        Schema::dropIfExists('trading_subscriptions');
    }
};
