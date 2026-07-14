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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');

            $table->unsignedBigInteger('source_configuration_id')->nullable();
            $table->unsignedInteger('source_configuration_version')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedSmallInteger('line_number');

            $table->string('type', 32);
            $table->string('sku_snapshot')->nullable();
            $table->string('name_snapshot');
            $table->text('description_snapshot')->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_in_cents');
            $table->unsignedBigInteger('subtotal_in_cents');
            $table->bigInteger('adjustment_total_in_cents')->default(0);
            $table->unsignedBigInteger('tax_in_cents')->default(0);
            $table->unsignedBigInteger('line_total_in_cents');

            $table->json('configuration_snapshot')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['order_id', 'line_number'], 'order_item_line_uq');

            $table->index(['order_id', 'type'], 'order_item_type_idx');
            $table->index('source_configuration_id', 'order_item_config_idx');
            $table->index('product_variant_id', 'order_item_variant_idx');

            $table->foreign('order_id', 'order_item_order_fk')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('source_configuration_id', 'order_item_config_fk')->references('id')->on('configurations')->nullOnDelete();
            $table->foreign('product_variant_id', 'order_item_variant_fk')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
