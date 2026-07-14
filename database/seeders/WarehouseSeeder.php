<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouse = Warehouse::withTrashed()->firstOrNew(['code' => 'main']);

        $warehouse->fill([
            'name' => 'Main warehouse',
            'priority' => 10,
            'is_active' => true,
            'can_assemble_systems' => true,
            'can_fulfill_orders' => true,
            'timezone' => 'Europe/Brussels',
            'country_code' => 'BE',
            'metadata' => null,
        ]);

        $warehouse->deleted_at = null;
        $warehouse->save();
    }
}
