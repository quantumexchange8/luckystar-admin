<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trade_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('meta_login')->nullable();
            $table->unsignedBigInteger('account_type_id')->nullable();
            $table->bigInteger('trade_deal_id')->nullable();
            $table->string('trade_symbol', 20)->nullable();
            $table->timestamp('trade_open_time')->nullable();
            $table->double('trade_open_price')->nullable();
            $table->timestamp('trade_close_time')->nullable();
            $table->double('trade_close_price')->nullable();
            $table->string('trade_type', 100)->nullable();
            $table->double('trade_lots')->nullable();
            $table->double('trade_profit')->nullable();
            $table->double('trade_sl')->nullable();
            $table->double('trade_tp')->nullable();
            $table->double('trade_commission')->nullable();
            $table->double('trade_swap')->nullable();
            $table->string('rebate_status', 20)->nullable();
            $table->timestamp('rebate_execute_at')->nullable();
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

    public function down(): void
    {
        Schema::dropIfExists('trade_histories');
    }
};
