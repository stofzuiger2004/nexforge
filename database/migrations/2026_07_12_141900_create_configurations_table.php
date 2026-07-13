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
            'configurations',
            function (Blueprint $table): void {
                $table->id();
                $table->ulid('public_id');

                $table
                    ->unsignedBigInteger('user_id')
                    ->nullable();

                $table
                    ->unsignedBigInteger('source_system_id')
                    ->nullable();

                $table->unsignedBigInteger('price_list_id');

                /*
                 * Anonymous customers receive a raw token in their
                 * session. Only its SHA-256 hash is stored here.
                 */
                $table
                    ->char('guest_token_hash', 64)
                    ->nullable();

                $table->string('name')->nullable();

                $table
                    ->string('status', 32)
                    ->default('draft');

                $table->char('currency', 3);

                /*
                 * Increase this whenever a configuration item changes.
                 * A validation run stores the version it validated.
                 */
                $table
                    ->unsignedInteger('version')
                    ->default(1);

                $table
                    ->unsignedBigInteger('subtotal_in_cents')
                    ->default(0);

                /*
                 * Signed because adjustments may be negative.
                 */
                $table
                    ->bigInteger('adjustment_total_in_cents')
                    ->default(0);

                $table
                    ->unsignedBigInteger('tax_in_cents')
                    ->default(0);

                $table
                    ->unsignedBigInteger('total_in_cents')
                    ->default(0);

                $table->timestamp('priced_at')->nullable();
                $table->timestamp('validated_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('last_activity_at')->nullable();

                $table->json('metadata')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    'public_id',
                    'cfg_public_id_uq',
                );

                $table->unique(
                    'guest_token_hash',
                    'cfg_guest_token_uq',
                );

                $table->index(
                    ['user_id', 'status'],
                    'cfg_user_status_idx',
                );

                $table->index(
                    ['status', 'expires_at'],
                    'cfg_status_exp_idx',
                );

                $table->index(
                    'source_system_id',
                    'cfg_source_system_idx',
                );

                $table
                    ->foreign(
                        'user_id',
                        'cfg_user_fk',
                    )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'source_system_id',
                        'cfg_system_fk',
                    )
                    ->references('id')
                    ->on('systems')
                    ->nullOnDelete();

                $table
                    ->foreign(
                        'price_list_id',
                        'cfg_price_list_fk',
                    )
                    ->references('id')
                    ->on('price_lists')
                    ->restrictOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
