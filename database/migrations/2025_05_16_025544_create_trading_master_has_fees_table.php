<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trading_master_has_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trading_master_id');
            $table->unsignedInteger('meta_login');
            $table->integer('management_days')->nullable();
            $table->decimal('management_percentage')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('trading_master_id')
                ->references('id')
                ->on('trading_masters')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_master_has_fees');
    }
};
