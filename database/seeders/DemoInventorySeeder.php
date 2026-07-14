<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryStockService;
use Illuminate\Database\Seeder;

class DemoInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouse = Warehouse::query()
            ->where('code', 'main')
            ->firstOrFail();

        $openingQuantity = (int) config(
            'inventory.demo_opening_quantity',
            25,
        );

        if ($openingQuantity < 1) {
            return;
        }

        $stockService = app(
            InventoryStockService::class,
        );

        ProductVariant::query()
            ->active()
            ->where('track_inventory', true)
            ->orderBy('id')
            ->each(
                function (
                    ProductVariant $variant,
                ) use (
                    $warehouse,
                    $openingQuantity,
                    $stockService,
                ): void {
                    $stockService->receive(
                        warehouse: $warehouse,
                        variant: $variant,
                        quantity: $openingQuantity,

                        idempotencyKey: sprintf(
                            'demo-opening:%s:%s',
                            $warehouse->code,
                            $variant->sku,
                        ),

                        reason: 'Demo opening inventory.',
                    );
                },
            );
    }
}
