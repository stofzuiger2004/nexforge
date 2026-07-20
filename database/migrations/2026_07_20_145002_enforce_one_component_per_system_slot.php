<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('system_components')
            ->select([
                'system_id',
                'slot',
            ])
            ->selectRaw('COUNT(*) as component_count')
            ->groupBy([
                'system_id',
                'slot',
            ])
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new \RuntimeException(sprintf(
                'System %s has multiple system_components rows for slot "%s". Resolve those duplicates before running this migration.',
                $duplicate->system_id,
                $duplicate->slot,
            ));
        }

        Schema::table(
            'system_components',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'system_components_slot_variant_unique',
                );

                $table->unique(
                    [
                        'system_id',
                        'slot',
                    ],
                    'sys_comp_system_slot_uq',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'system_components',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'sys_comp_system_slot_uq',
                );

                $table->unique(
                    [
                        'system_id',
                        'slot',
                        'product_variant_id',
                    ],
                    'system_components_slot_variant_unique',
                );
            },
        );
    }
};
