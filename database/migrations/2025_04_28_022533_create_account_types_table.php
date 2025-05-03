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
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('account_group')->nullable();
            $table->decimal('minimum_deposit')->nullable();
            $table->string('currency')->nullable();
            $table->integer('allow_create_account')->nullable();
            $table->string('commission_structure')->nullable();
            $table->string('trade_open_duration')->nullable();
            $table->integer('maximum_account_number')->nullable();
            $table->string('color', 100)->nullable();
            $table->string('status')->default('active');
            $table->boolean('allow_trade')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
