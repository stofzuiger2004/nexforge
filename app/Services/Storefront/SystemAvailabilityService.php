<?php

declare(strict_types=1);

namespace App\Services\Storefront;

use App\Models\InventoryItem;
use App\Models\System;
use App\Models\SystemComponent;
use Illuminate\Support\Collection;

final class SystemAvailabilityService
{
    /**
     * Returns:
     *
     * null  When none of the system components track inventory.
     * 0     When the system cannot currently be assembled.
     * > 0   Number of complete systems available from the best warehouse.
     */
    public function availableBuilds(System $system): ?int
    {
        $system->loadMissing([
            'components.variant.inventoryItems.warehouse',
        ]);

        $trackedComponents = $system
            ->components
            ->filter(
                static fn (SystemComponent $component): bool => $component->variant->track_inventory,
            );

        if ($trackedComponents->isEmpty()) {
            return null;
        }

        /*
         * The same variant could theoretically appear in more than
         * one system-component row. Group it so its total required
         * quantity is calculated correctly.
         */
        $requirements = $trackedComponents
            ->groupBy('product_variant_id')
            ->map(
                static function (Collection $components): array {
                    /** @var SystemComponent $firstComponent */
                    $firstComponent = $components->first();

                    return [
                        'variant' => $firstComponent->variant,

                        'quantity' => (int) $components
                            ->sum('quantity'),
                    ];
                },
            );

        /*
         * Collect every operational assembly warehouse that has
         * at least one relevant inventory row.
         */
        $warehouseIds = $requirements
            ->flatMap(
                function (array $requirement): Collection {
                    return $requirement['variant']
                        ->inventoryItems
                        ->filter(
                            fn (InventoryItem $inventoryItem): bool => $this->isUsableInventoryItem(
                                $inventoryItem,
                            ),
                        )
                        ->pluck('warehouse_id');
                },
            )
            ->unique()
            ->values();

        $bestAvailableBuilds = 0;

        foreach ($warehouseIds as $warehouseId) {
            $warehouseId = (int) $warehouseId;

            $buildsFromWarehouse = null;

            foreach ($requirements as $requirement) {
                $inventoryItem = $requirement['variant']
                    ->inventoryItems
                    ->first(
                        fn (InventoryItem $item): bool => (int) $item->warehouse_id === $warehouseId
                            && $this->isUsableInventoryItem($item),
                    );

                if ($inventoryItem === null) {
                    $buildsFromWarehouse = 0;

                    break;
                }

                $requiredQuantity = max(
                    1,
                    (int) $requirement['quantity'],
                );

                $possibleBuilds = intdiv(
                    $inventoryItem->reservableQuantity(),
                    $requiredQuantity,
                );

                $buildsFromWarehouse = $buildsFromWarehouse === null
                    ? $possibleBuilds
                    : min(
                        $buildsFromWarehouse,
                        $possibleBuilds,
                    );

                if ($buildsFromWarehouse === 0) {
                    break;
                }
            }

            $bestAvailableBuilds = max(
                $bestAvailableBuilds,
                $buildsFromWarehouse ?? 0,
            );
        }

        return $bestAvailableBuilds;
    }

    private function isUsableInventoryItem(
        InventoryItem $inventoryItem,
    ): bool {
        $warehouse = $inventoryItem->warehouse;

        return $inventoryItem->is_active
            && $warehouse !== null
            && $warehouse->is_active
            && $warehouse->can_assemble_systems;
    }
}
