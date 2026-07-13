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
            'configuration_adjustments',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'configuration_id',
                );

                $table->string('type', 32);
                $table->string('code', 80)->nullable();
                $table->string('label');

                /*
                 * Signed:
                 * fee      = positive
                 * discount = negative
                 */
                $table->bigInteger('amount_in_cents');

                $table
                    ->boolean('is_taxable')
                    ->default(true);

                $table
                    ->unsignedSmallInteger('sort_order')
                    ->default(0);

                $table->json('metadata')->nullable();

                $table->timestamps();

                $table->unique(
                    ['configuration_id', 'code'],
                    'cfg_adj_code_uq',
                );

                $table->index(
                    [
                        'configuration_id',
                        'type',
                        'sort_order',
                    ],
                    'cfg_adj_listing_idx',
                );

                $table
                    ->foreign(
                        'configuration_id',
                        'cfg_adj_config_fk',
                    )
                    ->references('id')
                    ->on('configurations')
                    ->cascadeOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'configuration_adjustments',
        );
    }
};
