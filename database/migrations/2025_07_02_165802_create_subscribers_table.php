<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('meta_login');
            $table->string('meta_account_type', 50);
            $table->unsignedInteger('master_login');
            $table->string('master_account_type', 50);
            $table->string('type', 50)->nullable();
            $table->string('subscriber_number', 50)->nullable()->unique();
            $table->integer('subscription_period')->nullable();
            $table->string('subscription_period_unit', 50)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('settlement_period')->nullable();
            $table->string('settlement_period_unit', 50)->nullable();
            $table->timestamp('settlement_start_at')->nullable();
            $table->timestamp('settlement_end_at')->nullable();
            $table->string('status', 100)->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('approval_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
