<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'configuration_items',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger('configuration_id');
                $table->unsignedBigInteger('product_variant_id');

                /*
                 * Tracks the component in the original prebuilt system.
                 * It becomes null for manually selected replacements.
                 */
                $table
                    ->unsignedBigInteger(
                        'source_system_component_id',
                    )
                    ->nullable();

                $table->string('slot', 64);

                $table
                    ->unsignedSmallInteger('quantity')
                    ->default(1);

                $table
                    ->unsignedSmallInteger('sort_order')
                    ->default(0);

                $table->string('sku_snapshot');
                $table->string('name_snapshot');

                $table->unsignedBigInteger(
                    'unit_price_in_cents',
                );

                $table->unsignedBigInteger(
                    'line_total_in_cents',
                );

                $table->json('metadata')->nullable();

                $table->timestamps();

                /*
                 * The same SKU should use quantity instead of
                 * appearing twice in the same slot.
                 */
                $table->unique(
                    [
                        'configuration_id',
                        'slot',
                        'product_variant_id',
                    ],
                    'cfg_item_slot_variant_uq',
                );

                $table->index(
                    [
                        'configuration_id',
                        'slot',
                        'sort_order',
                    ],
                    'cfg_item_listing_idx',
                );

                $table->index(
                    'product_variant_id',
                    'cfg_item_variant_idx',
                );

                $table
                    ->foreign(
                        'configuration_id',
                        'cfg_item_config_fk',
                    )
                    ->references('id')
                    ->on('configurations')
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'product_variant_id',
                        'cfg_item_variant_fk',
                    )
                    ->references('id')
                    ->on('product_variants')
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'source_system_component_id',
                        'cfg_item_source_fk',
                    )
                    ->references('id')
                    ->on('system_components')
                    ->nullOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_items');
    }
};
