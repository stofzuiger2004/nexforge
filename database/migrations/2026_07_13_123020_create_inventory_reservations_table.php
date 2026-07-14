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
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id');

            $table->unsignedBigInteger('configuration_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedInteger('configuration_version');

            $table->string('status', 32)->default('active');

            $table->string('idempotency_key', 120)->nullable();

            $table->timestamp('reserved_at');
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('consumed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique('public_id', 'inv_res_public_uq');
            $table->unique('idempotency_key', 'inv_res_idempotency_uq');

            $table->index(['configuration_id', 'status'], 'inv_res_config_status_idx');
            $table->index(['status', 'expires_at'], 'inv_res_expiry_idx');
            $table->index(['warehouse_id', 'status'], 'inv_res_wh_status_idx');

            $table->foreign('configuration_id', 'inv_res_config_fk')->references('id')->on('configurations')->restrictOnDelete();
            $table->foreign('warehouse_id', 'inv_res_wh_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'inv_res_creator_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
