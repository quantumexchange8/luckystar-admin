<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_type_has_leverages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_type_id');
            $table->unsignedBigInteger('setting_leverage_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_type_id')
                ->references('id')
                ->on('account_types')
                ->onUpdate('cascade');

            $table->foreign('setting_leverage_id')
                ->references('id')
                ->on('setting_leverages')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_type_has_leverages');
    }
};
