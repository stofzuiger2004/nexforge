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
        Schema::create('inventory_reservation_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('inventory_reservation_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('configuration_item_id')->nullable();
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('released_quantity')->default(0);
            $table->unsignedBigInteger('consumed_quantity')->default(0);

            $table->string('sku_snapshot');
            $table->string('name_snapshot');

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['inventory_reservation_id', 'inventory_item_id', 'configuration_item_id'], 'inv_res_item_allocation_uq');

            $table->index('inventory_item_id', 'inv_res_item_stock_idx');
            $table->index('configuration_item_id', 'inv_res_item_config_idx');

            $table->foreign('inventory_reservation_id', 'inv_res_item_res_fk')->references('id')->on('inventory_reservations')->cascadeOnDelete();
            $table->foreign('inventory_item_id', 'inv_res_item_stock_fk')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('configuration_item_id', 'inv_res_item_config_fk')->references('id')->on('configuration_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservation_items');
    }
};
