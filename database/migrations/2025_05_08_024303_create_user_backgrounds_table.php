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
        Schema::create('user_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('occupation_id')->nullable();
            $table->unsignedBigInteger('industry_id')->nullable();
            $table->string('source_of_income')->nullable();
            $table->string('annual_income')->nullable();
            $table->string('net_worth')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('trading_experience')->nullable();
            $table->string('traded_product_type')->nullable();
            $table->string('trade_per_month')->nullable();
            $table->string('trade_concept')->nullable();
            $table->string('trade_platform_experience')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('occupation_id')
                ->references('id')
                ->on('occupations')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('industry_id')
                ->references('id')
                ->on('industries')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_backgrounds');
    }
};
