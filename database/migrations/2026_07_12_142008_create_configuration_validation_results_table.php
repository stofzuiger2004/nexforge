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
            'configuration_validation_results',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'validation_run_id',
                );

                $table
                    ->unsignedBigInteger(
                        'compatibility_rule_id',
                    )
                    ->nullable();

                $table->string('rule_key');
                $table->string('rule_name');
                $table->unsignedInteger('rule_revision');

                $table->string('status', 16);
                $table->string('severity', 16);

                $table->text('message')->nullable();
                $table->json('context')->nullable();

                $table->timestamps();

                /*
                 * The initial engine produces one aggregated
                 * result per rule per validation run.
                 */
                $table->unique(
                    ['validation_run_id', 'rule_key'],
                    'cfg_val_result_rule_uq',
                );

                $table->index(
                    ['validation_run_id', 'status'],
                    'cfg_val_result_status_idx',
                );

                $table->index(
                    'compatibility_rule_id',
                    'cfg_val_result_rule_idx',
                );

                $table
                    ->foreign(
                        'validation_run_id',
                        'cfg_val_result_run_fk',
                    )
                    ->references('id')
                    ->on(
                        'configuration_validation_runs',
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreign(
                        'compatibility_rule_id',
                        'cfg_val_result_rule_fk',
                    )
                    ->references('id')
                    ->on('compatibility_rules')
                    ->nullOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'configuration_validation_results',
        );
    }
};
