<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class InventoryStockService
{
    public function __construct(
        private readonly InventoryLedgerService $ledger,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function receive(
        Warehouse $warehouse,
        ProductVariant $variant,
        int $quantity,
        ?User $actor = null,
        ?string $idempotencyKey = null,
        ?string $reason = null,
        array $metadata = [],
    ): InventoryMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'A stock receipt quantity must be at least one.',
            );
        }

        return DB::transaction(
            function () use (
                $warehouse,
                $variant,
                $quantity,
                $actor,
                $idempotencyKey,
                $reason,
                $metadata,
            ): InventoryMovement {
                $this->assertCanTrack(
                    $warehouse,
                    $variant,
                );

                $inventoryItem =
                    $this->lockOrCreateInventoryItem(
                        $warehouse,
                        $variant,
                    );

                return $this->ledger
                    ->applyToLockedItem(
                        inventoryItem: $inventoryItem,

                        type: InventoryMovementType::Receipt,

                        onHandDelta: $quantity,
                        reservedDelta: 0,

                        actor: $actor,

                        idempotencyKey: $idempotencyKey,

                        reason: $reason ?? 'Stock receipt',

                        metadata: $metadata,
                    );
            },
            attempts: 3,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function adjustBy(
        InventoryItem $inventoryItem,
        int $quantityDelta,
        string $reason,
        ?User $actor = null,
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): InventoryMovement {
        if ($quantityDelta === 0) {
            throw new InvalidArgumentException(
                'An inventory adjustment cannot be zero.',
            );
        }

        return DB::transaction(
            function () use (
                $inventoryItem,
                $quantityDelta,
                $reason,
                $actor,
                $idempotencyKey,
                $metadata,
            ): InventoryMovement {
                $lockedItem = InventoryItem::query()
                    ->whereKey($inventoryItem->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                return $this->ledger
                    ->applyToLockedItem(
                        inventoryItem: $lockedItem,

                        type: InventoryMovementType::Adjustment,

                        onHandDelta: $quantityDelta,
                        reservedDelta: 0,

                        actor: $actor,

                        idempotencyKey: $idempotencyKey,

                        reason: $reason,
                        metadata: $metadata,
                    );
            },
            attempts: 3,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function setPhysicalCount(
        InventoryItem $inventoryItem,
        int $countedQuantity,
        string $reason,
        ?User $actor = null,
        ?string $idempotencyKey = null,
        array $metadata = [],
    ): ?InventoryMovement {
        if ($countedQuantity < 0) {
            throw new InvalidArgumentException(
                'A physical stock count cannot be negative.',
            );
        }

        return DB::transaction(
            function () use (
                $inventoryItem,
                $countedQuantity,
                $reason,
                $actor,
                $idempotencyKey,
                $metadata,
            ): ?InventoryMovement {
                $lockedItem = InventoryItem::query()
                    ->whereKey($inventoryItem->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $difference =
                    $countedQuantity
                    - $lockedItem->quantity_on_hand;

                if ($difference === 0) {
                    $lockedItem->forceFill([
                        'last_counted_at' => now(),
                    ])->save();

                    return null;
                }

                $movement = $this->ledger
                    ->applyToLockedItem(
                        inventoryItem: $lockedItem,

                        type: InventoryMovementType::Adjustment,

                        onHandDelta: $difference,
                        reservedDelta: 0,

                        actor: $actor,

                        idempotencyKey: $idempotencyKey,

                        reason: $reason,

                        metadata: [
                            ...$metadata,
                            'counted_quantity' => $countedQuantity,
                        ],
                    );

                $lockedItem->forceFill([
                    'last_counted_at' => now(),
                ])->save();

                return $movement;
            },
            attempts: 3,
        );
    }

    /**
     * @return array{
     *     out: InventoryMovement,
     *     in: InventoryMovement
     * }
     */
    public function transfer(
        InventoryItem $sourceInventoryItem,
        Warehouse $targetWarehouse,
        int $quantity,
        string $reason,
        ?User $actor = null,
        ?string $idempotencyKey = null,
    ): array {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'A transfer quantity must be at least one.',
            );
        }

        return DB::transaction(
            function () use (
                $sourceInventoryItem,
                $targetWarehouse,
                $quantity,
                $reason,
                $actor,
                $idempotencyKey,
            ): array {
                $source = InventoryItem::query()
                    ->with('variant')
                    ->findOrFail(
                        $sourceInventoryItem->id,
                    );

                if (
                    $source->warehouse_id
                    === $targetWarehouse->id
                ) {
                    throw new DomainException(
                        'Source and target warehouses must be different.',
                    );
                }

                if (! $targetWarehouse->is_active) {
                    throw new DomainException(
                        'The target warehouse is not active.',
                    );
                }

                $target =
                    $this->lockOrCreateInventoryItem(
                        $targetWarehouse,
                        $source->variant,
                    );

                /*
                 * Lock both rows in a deterministic ID order.
                 */
                $lockedItems = InventoryItem::query()
                    ->whereIn('id', [
                        $source->id,
                        $target->id,
                    ])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $lockedSource =
                    $lockedItems->get($source->id);

                $lockedTarget =
                    $lockedItems->get($target->id);

                if (
                    $lockedSource === null
                    || $lockedTarget === null
                ) {
                    throw new DomainException(
                        'The transfer inventory rows could not be locked.',
                    );
                }

                if (
                    $lockedSource->reservableQuantity()
                    < $quantity
                ) {
                    throw new DomainException(
                        'The source warehouse does not have enough transferable stock.',
                    );
                }

                $correlationId =
                    (string) Str::ulid();

                $outMovement =
                    $this->ledger
                        ->applyToLockedItem(
                            inventoryItem: $lockedSource,

                            type: InventoryMovementType::TransferOut,

                            onHandDelta: -$quantity,

                            reservedDelta: 0,
                            actor: $actor,

                            idempotencyKey: $idempotencyKey === null
                                    ? null
                                    : $idempotencyKey.':out',

                            correlationId: $correlationId,

                            reason: $reason,

                            metadata: [
                                'target_warehouse_id' => $targetWarehouse->id,
                            ],
                        );

                $inMovement =
                    $this->ledger
                        ->applyToLockedItem(
                            inventoryItem: $lockedTarget,

                            type: InventoryMovementType::TransferIn,

                            onHandDelta: $quantity,

                            reservedDelta: 0,
                            actor: $actor,

                            idempotencyKey: $idempotencyKey === null
                                    ? null
                                    : $idempotencyKey.':in',

                            correlationId: $correlationId,

                            reason: $reason,

                            metadata: [
                                'source_warehouse_id' => $lockedSource
                                    ->warehouse_id,
                            ],
                        );

                return [
                    'out' => $outMovement,
                    'in' => $inMovement,
                ];
            },
            attempts: 3,
        );
    }

    private function assertCanTrack(
        Warehouse $warehouse,
        ProductVariant $variant,
    ): void {
        if (! $warehouse->is_active) {
            throw new DomainException(
                'The warehouse is not active.',
            );
        }

        if (! $variant->track_inventory) {
            throw new DomainException(
                'This product variant does not track inventory.',
            );
        }
    }

    private function lockOrCreateInventoryItem(
        Warehouse $warehouse,
        ProductVariant $variant,
    ): InventoryItem {
        /*
         * Upsert avoids a race where two requests both try
         * to create the same warehouse/SKU row.
         */
        InventoryItem::query()->upsert(
            [
                [
                    'warehouse_id' => $warehouse->id,

                    'product_variant_id' => $variant->id,

                    'is_active' => true,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'safety_stock' => 0,
                    'reorder_point' => null,
                    'lock_version' => 1,

                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            [
                'warehouse_id',
                'product_variant_id',
            ],
            [
                'updated_at',
            ],
        );

        return InventoryItem::query()
            ->where(
                'warehouse_id',
                $warehouse->id,
            )
            ->where(
                'product_variant_id',
                $variant->id,
            )
            ->lockForUpdate()
            ->firstOrFail();
    }
}
