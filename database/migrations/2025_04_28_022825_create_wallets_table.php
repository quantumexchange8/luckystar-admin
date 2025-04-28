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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->string('address');
            $table->string('currency', 30)->nullable();
            $table->string('currency_symbol', 100)->nullable();
            $table->decimal('balance', 13)->default(0);
            $table->tinyInteger('balance_visibility')->default(1);
            $table->decimal('demo_fund', 13)->default(0);
            $table->decimal('real_fund', 13)->default(0);
            $table->string('status', 100)->nullable()->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
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
        Schema::dropIfExists('wallets');
    }
};
