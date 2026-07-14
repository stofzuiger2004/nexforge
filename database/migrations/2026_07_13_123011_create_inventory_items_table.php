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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->string('bin_location', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('quantity_on_hand')->default(0);
            $table->unsignedBigInteger('quantity_reserved')->default(0);
            $table->unsignedBigInteger('safety_stock')->default(0);
            $table->unsignedBigInteger('reorder_point')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_variant_id'], 'inv_item_wh_variant_uq');

            $table->index(['product_variant_id', 'is_active'], 'inv_item_variant_idx');

            $table->index(['warehouse_id', 'is_active'], 'inv_item_wh_active_idx');

            $table->foreign('warehouse_id', 'inv_item_wh_fk')->references('id')->on('warehouses')->restrictOnDelete();

            $table->foreign('product_variant_id', 'inv_item_variant_fk')->references('id')->on('product_variants')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
