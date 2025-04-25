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
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('rank_name')->nullable();
            $table->integer('rank_position')->nullable();
            $table->string('lot_rebate_currency')->nullable();
            $table->decimal('lot_rebate_amount')->nullable();
            $table->integer('min_direct_referral')->nullable();
            $table->unsignedBigInteger('min_direct_referral_rank_id')->nullable();
            $table->decimal('min_amount_per_person', 13)->nullable();
            $table->decimal('min_group_sales', 13)->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('min_direct_referral_rank_id')
                ->references('id')
                ->on('ranks')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('edited_by')
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
        Schema::dropIfExists('ranks');
    }
};
