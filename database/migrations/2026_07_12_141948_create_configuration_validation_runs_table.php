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
            'configuration_validation_runs',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'configuration_id',
                );

                $table
                    ->string('status', 32)
                    ->default('running');

                $table->unsignedInteger(
                    'configuration_version',
                );

                $table
                    ->unsignedInteger('rules_count')
                    ->default(0);

                $table
                    ->unsignedInteger('passed_count')
                    ->default(0);

                $table
                    ->unsignedInteger('failed_count')
                    ->default(0);

                $table
                    ->unsignedInteger('warning_count')
                    ->default(0);

                $table
                    ->unsignedInteger('skipped_count')
                    ->default(0);

                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();

                $table->text('error_message')->nullable();

                $table->timestamps();

                $table->index(
                    ['configuration_id', 'created_at'],
                    'cfg_val_run_config_idx',
                );

                $table->index(
                    'status',
                    'cfg_val_run_status_idx',
                );

                $table
                    ->foreign(
                        'configuration_id',
                        'cfg_val_run_config_fk',
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
            'configuration_validation_runs',
        );
    }
};
