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
        Schema::create('group_rank_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('rank_name')->nullable();
            $table->string('rank_code', 20)->nullable();
            $table->integer('rank_position')->nullable();
            $table->string('lot_rebate_currency')->nullable();
            $table->decimal('lot_rebate_amount')->nullable();
            $table->integer('min_direct_referral')->nullable();
            $table->unsignedBigInteger('min_direct_referral_rank_id')->nullable();
            $table->string('group_sales_currency')->nullable();
            $table->decimal('max_capped_per_line', 13)->nullable();
            $table->decimal('min_group_sales', 13)->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('min_direct_referral_rank_id')
                ->references('id')
                ->on('group_rank_settings')
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
        Schema::dropIfExists('group_rank_settings');
    }
};
