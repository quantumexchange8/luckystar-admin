<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->decimal('subscription_amount')->nullable();
            $table->decimal('real_fund')->nullable();
            $table->decimal('demo_fund')->nullable();
            $table->string('subscription_number', 50)->nullable()->unique();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('subscriber_id')
                ->references('id')
                ->on('subscribers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
