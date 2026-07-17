<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Exceptions\StaleResourceVersionException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

final class AdminInventorySettingsUpdater
{
    /**
     * @param array{
     *     bin_location: string|null,
     *     safety_stock: int,
     *     reorder_point: int|null,
     *     is_active: bool
     * } $values
     */
    public function update(
        InventoryItem $inventoryItem,
        array $values,
        int $expectedLockVersion,
    ): InventoryItem {
        return DB::transaction(
            function () use (
                $inventoryItem,
                $values,
                $expectedLockVersion,
            ): InventoryItem {
                $lockedItem = InventoryItem::query()
                    ->whereKey(
                        $inventoryItem->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedItem->lock_version
                    !== $expectedLockVersion
                ) {
                    throw StaleResourceVersionException::forInventoryItem();
                }

                $lockedItem->forceFill([
                    'bin_location' => $values[
                        'bin_location'
                    ],

                    'safety_stock' => $values[
                        'safety_stock'
                    ],

                    'reorder_point' => $values[
                        'reorder_point'
                    ],

                    'is_active' => $values[
                        'is_active'
                    ],

                    'lock_version' => $lockedItem
                        ->lock_version
                        + 1,
                ])->saveOrFail();

                return $lockedItem->refresh();
            },
            attempts: 3,
        );
    }
}