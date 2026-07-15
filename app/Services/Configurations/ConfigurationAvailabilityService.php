<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\ComponentSlot;
use App\Models\Configuration;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

final class ConfigurationAvailabilityService
{
    /**
     * Returns:
     *
     * null  No selected components track inventory.
     * 0     No single assembly warehouse can fulfill the build.
     * > 0   Number of complete builds available at the best warehouse.
     */
    public function availableBuilds(
        Configuration $configuration,
        ?ComponentSlot $replacementSlot = null,
        ?ProductVariant $replacementVariant = null,
    ): ?int {
        $configuration->loadMissing([
            'items.variant.inventoryItems.warehouse',
        ]);

        $replacementVariant?->loadMissing([
            'inventoryItems.warehouse',
        ]);

        $requirements = $this->buildRequirements(
            $configuration,
            $replacementSlot,
            $replacementVariant,
        );

        if ($requirements->isEmpty()) {
            return null;
        }

        $warehouseIds = $requirements
            ->flatMap(
                function (array $requirement): Collection {
                    /** @var ProductVariant $variant */
                    $variant = $requirement['variant'];

                    return $variant
                        ->inventoryItems
                        ->filter(
                            fn (
                                InventoryItem $inventoryItem,
                            ): bool => $this->isUsable(
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
            $buildsAtWarehouse = null;

            foreach ($requirements as $requirement) {
                /** @var ProductVariant $variant */
                $variant = $requirement['variant'];

                $inventoryItem = $variant
                    ->inventoryItems
                    ->first(
                        fn (
                            InventoryItem $item,
                        ): bool => (int) $item->warehouse_id
                                === (int) $warehouseId
                            && $this->isUsable($item),
                    );

                if ($inventoryItem === null) {
                    $buildsAtWarehouse = 0;

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

                $buildsAtWarehouse =
                    $buildsAtWarehouse === null
                        ? $possibleBuilds
                        : min(
                            $buildsAtWarehouse,
                            $possibleBuilds,
                        );

                if ($buildsAtWarehouse === 0) {
                    break;
                }
            }

            $bestAvailableBuilds = max(
                $bestAvailableBuilds,
                $buildsAtWarehouse ?? 0,
            );
        }

        return $bestAvailableBuilds;
    }

    /**
     * @return Collection<int, array{
     *     variant: ProductVariant,
     *     quantity: int
     * }>
     */
    private function buildRequirements(
        Configuration $configuration,
        ?ComponentSlot $replacementSlot,
        ?ProductVariant $replacementVariant,
    ): Collection {
        $requirements = collect();

        foreach ($configuration->items as $item) {
            $variant =
                $replacementSlot !== null
                && $replacementVariant !== null
                && $item->slot === $replacementSlot
                    ? $replacementVariant
                    : $item->variant;

            if (! $variant->track_inventory) {
                continue;
            }

            $existing = $requirements->get(
                $variant->id,
                [
                    'variant' => $variant,
                    'quantity' => 0,
                ],
            );

            $existing['quantity'] +=
                $item->quantity;

            $requirements->put(
                $variant->id,
                $existing,
            );
        }

        return $requirements;
    }

    private function isUsable(
        InventoryItem $inventoryItem,
    ): bool {
        $warehouse = $inventoryItem->warehouse;

        return $inventoryItem->is_active
            && $warehouse !== null
            && $warehouse->is_active
            && $warehouse->can_assemble_systems;
    }
}
