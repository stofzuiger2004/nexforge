<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\InventoryReservationItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class InventoryLedgerService
{
    /**
     * The caller must already be inside a transaction and must
     * have locked the inventory item with lockForUpdate().
     *
     * @param  array<string, mixed>  $metadata
     */
    public function applyToLockedItem(
        InventoryItem $inventoryItem,
        InventoryMovementType $type,
        int $onHandDelta,
        int $reservedDelta,
        ?InventoryReservation $reservation = null,
        ?InventoryReservationItem $reservationItem = null,
        ?User $actor = null,
        ?string $idempotencyKey = null,
        ?string $correlationId = null,
        ?string $reason = null,
        array $metadata = [],
    ): InventoryMovement {
        if (
            DB::connection()->transactionLevel()
            < 1
        ) {
            throw new LogicException(
                'Inventory movements must be applied inside a database transaction.',
            );
        }

        if (
            $onHandDelta === 0
            && $reservedDelta === 0
        ) {
            throw new InvalidArgumentException(
                'An inventory movement must change at least one balance.',
            );
        }

        if (
            $idempotencyKey !== null
            && mb_strlen($idempotencyKey) > 120
        ) {
            throw new InvalidArgumentException(
                'The inventory movement idempotency key is too long.',
            );
        }

        if ($idempotencyKey !== null) {
            $existing = InventoryMovement::query()
                ->where(
                    'idempotency_key',
                    $idempotencyKey,
                )
                ->first();

            if ($existing !== null) {
                if (
                    $existing->inventory_item_id
                    !== $inventoryItem->id
                ) {
                    throw new LogicException(
                        'The idempotency key belongs to another inventory item.',
                    );
                }

                return $existing;
            }
        }

        $newOnHand =
            $inventoryItem->quantity_on_hand
            + $onHandDelta;

        $newReserved =
            $inventoryItem->quantity_reserved
            + $reservedDelta;

        if ($newOnHand < 0) {
            throw new DomainException(
                'Inventory on-hand quantity cannot become negative.',
            );
        }

        if ($newReserved < 0) {
            throw new DomainException(
                'Inventory reserved quantity cannot become negative.',
            );
        }

        if ($newReserved > $newOnHand) {
            throw new DomainException(
                'Reserved stock cannot exceed stock on hand.',
            );
        }

        $inventoryItem->forceFill([
            'quantity_on_hand' => $newOnHand,
            'quantity_reserved' => $newReserved,

            'lock_version' => $inventoryItem->lock_version + 1,
        ])->save();

        return InventoryMovement::query()->create([
            'inventory_item_id' => $inventoryItem->id,

            'inventory_reservation_id' => $reservation?->id,

            'inventory_reservation_item_id' => $reservationItem?->id,

            'actor_user_id' => $actor?->id,

            'type' => $type,

            'on_hand_delta' => $onHandDelta,
            'reserved_delta' => $reservedDelta,

            'on_hand_after' => $newOnHand,
            'reserved_after' => $newReserved,

            'correlation_id' => $correlationId,
            'idempotency_key' => $idempotencyKey,
            'reason' => $reason,

            'metadata' => $metadata === []
                    ? null
                    : $metadata,

            'occurred_at' => now(),
        ]);
    }
}
