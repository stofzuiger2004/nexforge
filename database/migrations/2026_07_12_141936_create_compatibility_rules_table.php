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
            'compatibility_rules',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string('key')
                    ->unique('compat_rule_key_uq');

                $table->string('name');
                $table->text('description')->nullable();

                $table->string('rule_type', 48);
                $table
                    ->string('severity', 16)
                    ->default('error');

                $table->string('source_slot', 64);

                $table
                    ->unsignedBigInteger(
                        'source_specification_id',
                    )
                    ->nullable();

                $table
                    ->string('target_slot', 64)
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'target_specification_id',
                    )
                    ->nullable();

                $table->text('failure_message');
                $table->json('settings')->nullable();

                /*
                 * Increment this whenever the rule semantics change.
                 */
                $table
                    ->unsignedInteger('revision')
                    ->default(1);

                $table
                    ->unsignedSmallInteger('sort_order')
                    ->default(0);

                $table
                    ->boolean('is_active')
                    ->default(true);

                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    ['is_active', 'sort_order'],
                    'compat_rule_active_idx',
                );

                $table->index(
                    'rule_type',
                    'compat_rule_type_idx',
                );

                $table
                    ->foreign(
                        'source_specification_id',
                        'compat_rule_src_spec_fk',
                    )
                    ->references('id')
                    ->on('specifications')
                    ->restrictOnDelete();

                $table
                    ->foreign(
                        'target_specification_id',
                        'compat_rule_tgt_spec_fk',
                    )
                    ->references('id')
                    ->on('specifications')
                    ->restrictOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('compatibility_rules');
    }
};
