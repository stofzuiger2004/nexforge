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
        Schema::create('order_item_components', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_item_id');
            $table->unsignedBigInteger('source_configuration_item_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedSmallInteger('line_number');

            $table->string('slot', 64);
            $table->string('sku_snapshot');
            $table->string('name_snapshot');

            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_in_cents')->nullable();
            $table->unsignedBigInteger('line_total_in_cents')->nullable();

            $table->json('specifications_snapshot')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['order_item_id', 'line_number'], 'order_component_line_uq');

            $table->index(['order_item_id', 'slot'], 'order_component_slot_idx');
            $table->index('product_variant_id', 'order_component_variant_idx');

            $table->foreign('order_item_id', 'order_component_item_fk')->references('id')->on('order_items')->restrictOnDelete();
            $table->foreign('source_configuration_item_id', 'order_component_config_fk')->references('id')->on('configuration_items')->nullOnDelete();
            $table->foreign('product_variant_id', 'order_component_variant_fk')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_components');
    }
};
