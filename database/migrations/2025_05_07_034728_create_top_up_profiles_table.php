<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('top_up_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('payment_name');
            $table->string('type');
            $table->string('payment_app_name')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('secondary_key')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_address')->nullable();
            $table->string('swift_code')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('currency')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('top_up_profiles');
    }
};
