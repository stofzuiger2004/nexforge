<?php

declare(strict_types=1);
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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id');

            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('inventory_reservation_id')->nullable();
            $table->unsignedBigInteger('inventory_reservation_item_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();

            $table->string('type', 48);

            $table->bigInteger('on_hand_delta');
            $table->bigInteger('reserved_delta');

            $table->unsignedBigInteger('on_hand_after');
            $table->unsignedBigInteger('reserved_after');

            $table->ulid('correlation_id')->nullable();

            $table->string('idempotency_key', 120)->nullable();
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique('public_id', 'inv_move_public_uq');
            $table->unique('idempotency_key', 'inv_move_idempotency_uq');

            $table->index(['inventory_item_id', 'occurred_at'], 'inv_move_stock_date_idx');
            $table->index('inventory_reservation_id', 'inv_move_res_idx');
            $table->index('inventory_reservation_item_id', 'inv_move_res_item_idx');
            $table->index('correlation_id', 'inv_move_correlation_idx');
            $table->index('type', 'inv_move_type_idx');

            $table->foreign('inventory_item_id', 'inv_move_stock_fk')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('inventory_reservation_id', 'inv_move_res_fk')->references('id')->on('inventory_reservations')->nullOnDelete();
            $table->foreign('inventory_reservation_item_id', 'inv_move_res_item_fk')->references('id')->on('inventory_reservation_items')->nullOnDelete();
            $table->foreign('actor_user_id', 'inv_move_actor_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
