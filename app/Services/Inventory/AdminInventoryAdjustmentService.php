<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\AdminInventoryAdjustmentType;
use App\Enums\InventoryMovementType;
use App\Exceptions\StaleResourceVersionException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class AdminInventoryAdjustmentService
{
    public function __construct(
        private readonly InventoryLedgerService $ledger,
    ) {}

    public function adjust(
        InventoryItem $inventoryItem,
        AdminInventoryAdjustmentType $type,
        int $quantity,
        string $reason,
        int $expectedLockVersion,
        string $idempotencyKey,
        ?string $reference,
        User $actor,
    ): InventoryMovement {
        return DB::transaction(
            function () use (
                $inventoryItem,
                $type,
                $quantity,
                $reason,
                $expectedLockVersion,
                $idempotencyKey,
                $reference,
                $actor,
            ): InventoryMovement {
                $lockedItem = InventoryItem::query()
                    ->whereKey(
                        $inventoryItem->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                 * Check idempotency before checking the
                 * submitted version. A browser retry uses the
                 * original version number.
                 */
                $existing = InventoryMovement::query()
                    ->where(
                        'idempotency_key',
                        $idempotencyKey,
                    )
                    ->first();

                if ($existing !== null) {
                    if (
                        $existing->inventory_item_id
                        !== $lockedItem->id
                    ) {
                        throw new LogicException(
                            'The idempotency key belongs to another inventory item.',
                        );
                    }

                    return $existing;
                }

                if (
                    $lockedItem->lock_version
                    !== $expectedLockVersion
                ) {
                    throw StaleResourceVersionException::forInventoryItem();
                }

                if (
                    $type
                        !== AdminInventoryAdjustmentType::StockCount
                    && $quantity < 1
                ) {
                    throw new InvalidArgumentException(
                        'The adjustment quantity must be at least one.',
                    );
                }

                [$movementType, $onHandDelta] =
                    match ($type) {
                        AdminInventoryAdjustmentType::Receipt => [
                            InventoryMovementType::Receipt,
                            $quantity,
                        ],

                        AdminInventoryAdjustmentType::CorrectionIncrease => [
                            InventoryMovementType::Adjustment,
                            $quantity,
                        ],

                        AdminInventoryAdjustmentType::CorrectionDecrease => [
                            InventoryMovementType::Adjustment,
                            -$quantity,
                        ],

                        AdminInventoryAdjustmentType::Damaged => [
                            InventoryMovementType::Damaged,
                            -$quantity,
                        ],

                        AdminInventoryAdjustmentType::StockCount => [
                            InventoryMovementType::StockCount,
                            $quantity
                            - $lockedItem
                                ->quantity_on_hand,
                        ],
                    };

                if (
                    $type
                        === AdminInventoryAdjustmentType::StockCount
                    && $quantity
                        < $lockedItem
                            ->quantity_reserved
                ) {
                    throw new DomainException(
                        'The counted quantity cannot be lower than the quantity currently reserved.',
                    );
                }

                $movement = $this->ledger
                    ->applyToLockedItem(
                        inventoryItem: $lockedItem,
                        type: $movementType,
                        onHandDelta: $onHandDelta,
                        reservedDelta: 0,
                        actor: $actor,
                        idempotencyKey: $idempotencyKey,
                        correlationId: (string) Str::ulid(),
                        reason: $reason,
                        metadata: [
                            'admin_adjustment_type' => $type
                                ->value,

                            'reference' => $reference,

                            'submitted_quantity' => $quantity,

                            'expected_lock_version' => $expectedLockVersion,
                        ],
                    );

                if (
                    $type
                    === AdminInventoryAdjustmentType::StockCount
                ) {
                    $lockedItem->forceFill([
                        'last_counted_at' => now(),
                    ])->saveOrFail();
                }

                return $movement;
            },
            attempts: 3,
        );
    }
}