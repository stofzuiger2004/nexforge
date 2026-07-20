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
        Schema::create('system_price_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_price_id');
            $table->unsignedBigInteger('system_id');
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('old_amount_in_cents');
            $table->unsignedBigInteger('new_amount_in_cents');
            $table->unsignedBigInteger('old_compare_at_amount_in_cents')->nullable();
            $table->unsignedBigInteger('new_compare_at_amount_in_cents')->nullable();
            $table->unsignedBigInteger('lock_version_before');
            $table->unsignedBigInteger('lock_version_after');
            $table->string('reason', 500);
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['system_price_id', 'changed_at'],'sph_price_changed_idx');
            $table->index(['system_id', 'price_list_id'],'sph_system_list_idx');
            $table->foreign('system_price_id', 'sph_price_fk')->references('id')->on('system_prices')->restrictOnDelete();
            $table->foreign('system_id', 'sph_system_fk')->references('id')->on('systems')->restrictOnDelete();
            $table->foreign('price_list_id', 'sph_list_fk')->references('id')->on('price_lists')->restrictOnDelete();
            $table->foreign('actor_user_id', 'sph_actor_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_price_histories');
    }
};
