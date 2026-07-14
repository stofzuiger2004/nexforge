<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\ConfigurationStatus;
use App\Enums\InventoryMovementType;
use App\Enums\InventoryReservationStatus;
use App\Enums\ValidationRunStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Configuration;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\InventoryReservationItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class InventoryReservationService
{
    public function __construct(
        private readonly InventoryLedgerService $ledger,
    ) {}

    public function reserveConfiguration(
        Configuration $configuration,
        ?User $actor = null,
        ?string $idempotencyKey = null,
        ?CarbonInterface $expiresAt = null,
    ): InventoryReservation {
        if (
            $idempotencyKey !== null
            && mb_strlen($idempotencyKey) > 120
        ) {
            throw new InvalidArgumentException(
                'The reservation idempotency key is too long.',
            );
        }

        return DB::transaction(
            function () use (
                $configuration,
                $actor,
                $idempotencyKey,
                $expiresAt,
            ): InventoryReservation {
                if ($idempotencyKey !== null) {
                    $existing =
                        InventoryReservation::query()
                            ->where(
                                'idempotency_key',
                                $idempotencyKey,
                            )
                            ->first();

                    if ($existing !== null) {
                        if (
                            $existing->configuration_id
                            !== $configuration->id
                        ) {
                            throw new DomainException(
                                'The reservation idempotency key belongs to another configuration.',
                            );
                        }

                        return $this
                            ->loadReservation($existing);
                    }
                }

                $lockedConfiguration =
                    Configuration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $configuration->id,
                        );

                $this->assertConfigurationCanReserve(
                    $lockedConfiguration,
                );

                $lockedConfiguration->load([
                    'items.variant.product.category',
                ]);

                $trackedItems =
                    $lockedConfiguration
                        ->items
                        ->filter(
                            static fn ($item): bool => $item
                                ->variant
                                ->track_inventory,
                        );

                if ($trackedItems->isEmpty()) {
                    throw new DomainException(
                        'This configuration contains no inventory-tracked components.',
                    );
                }

                /*
                 * Return the current active reservation when it
                 * already represents this exact configuration version.
                 * Older active reservations are cancelled first.
                 */
                $activeReservations =
                    InventoryReservation::query()
                        ->where(
                            'configuration_id',
                            $lockedConfiguration->id,
                        )
                        ->active()
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                foreach (
                    $activeReservations as $activeReservation
                ) {
                    if (
                        ! $activeReservation->isExpired()
                        && $activeReservation
                            ->configuration_version
                            === $lockedConfiguration->version
                    ) {
                        return $this->loadReservation(
                            $activeReservation,
                        );
                    }

                    $status =
                        $activeReservation->isExpired()
                            ? InventoryReservationStatus::Expired
                            : InventoryReservationStatus::Cancelled;

                    $this->releaseLockedReservation(
                        reservation: $activeReservation,

                        terminalStatus: $status,

                        actor: $actor,

                        reason: $status
                            === InventoryReservationStatus::Expired
                                ? 'Reservation expired.'
                                : 'Reservation replaced by a newer configuration version.',
                    );
                }

                $now = now();

                $expiration =
                    $expiresAt
                    ?? $now->copy()->addMinutes(
                        (int) config(
                            'inventory.reservation_ttl_minutes',
                            15,
                        ),
                    );

                if ($expiration->lte($now)) {
                    throw new InvalidArgumentException(
                        'The reservation expiration must be in the future.',
                    );
                }

                $requirements =
                    $this->buildRequirements(
                        $trackedItems,
                    );

                [
                    $warehouse,
                    $lockedInventoryItems,
                ] = $this->lockWarehouseWithStock(
                    $requirements,
                );

                $reservation =
                    InventoryReservation::query()
                        ->create([
                            'configuration_id' => $lockedConfiguration->id,

                            'warehouse_id' => $warehouse->id,

                            'created_by_user_id' => $actor?->id
                                ?? $lockedConfiguration
                                    ->user_id,

                            'configuration_version' => $lockedConfiguration
                                ->version,

                            'status' => InventoryReservationStatus::Active,

                            'idempotency_key' => $idempotencyKey,

                            'reserved_at' => $now,
                            'expires_at' => $expiration,

                            'metadata' => [
                                'configuration_public_id' => $lockedConfiguration
                                    ->public_id,
                            ],
                        ]);

                foreach (
                    $trackedItems->sortBy('id') as $configurationItem
                ) {
                    $inventoryItem =
                        $lockedInventoryItems->get(
                            $configurationItem
                                ->product_variant_id,
                        );

                    if ($inventoryItem === null) {
                        throw new DomainException(
                            'A locked inventory item disappeared during reservation.',
                        );
                    }

                    $reservationItem =
                        $reservation
                            ->items()
                            ->create([
                                'inventory_item_id' => $inventoryItem->id,

                                'configuration_item_id' => $configurationItem->id,

                                'quantity' => $configurationItem
                                    ->quantity,

                                'released_quantity' => 0,
                                'consumed_quantity' => 0,

                                'sku_snapshot' => $configurationItem
                                    ->sku_snapshot,

                                'name_snapshot' => $configurationItem
                                    ->name_snapshot,

                                'metadata' => null,
                            ]);

                    $this->ledger
                        ->applyToLockedItem(
                            inventoryItem: $inventoryItem,

                            type: InventoryMovementType::ReservationCreated,

                            onHandDelta: 0,

                            reservedDelta: $configurationItem
                                ->quantity,

                            reservation: $reservation,

                            reservationItem: $reservationItem,

                            actor: $actor,

                            idempotencyKey: sprintf(
                                'reservation:%s:create:%d',
                                $reservation
                                    ->public_id,
                                $reservationItem
                                    ->id,
                            ),

                            reason: 'Configuration stock reservation.',
                        );
                }

                return $this->loadReservation(
                    $reservation,
                );
            },
            attempts: 3,
        );
    }

    public function release(
        InventoryReservation $reservation,
        ?User $actor = null,
        string $reason = 'Reservation released.',
    ): InventoryReservation {
        return $this->terminateReservation(
            reservation: $reservation,

            terminalStatus: InventoryReservationStatus::Released,

            actor: $actor,
            reason: $reason,
        );
    }

    public function cancel(
        InventoryReservation $reservation,
        ?User $actor = null,
        string $reason = 'Reservation cancelled.',
    ): InventoryReservation {
        return $this->terminateReservation(
            reservation: $reservation,

            terminalStatus: InventoryReservationStatus::Cancelled,

            actor: $actor,
            reason: $reason,
        );
    }

    public function expire(
        InventoryReservation $reservation,
    ): InventoryReservation {
        return DB::transaction(
            function () use (
                $reservation,
            ): InventoryReservation {
                $lockedReservation =
                    InventoryReservation::query()
                        ->whereKey($reservation->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedReservation->status
                    !== InventoryReservationStatus::Active
                ) {
                    return $this->loadReservation(
                        $lockedReservation,
                    );
                }

                /*
                 * Another process may have extended the expiry
                 * after the expiration command loaded the row.
                 */
                if (
                    ! $lockedReservation->isExpired()
                ) {
                    return $this->loadReservation(
                        $lockedReservation,
                    );
                }

                return $this
                    ->releaseLockedReservation(
                        reservation: $lockedReservation,

                        terminalStatus: InventoryReservationStatus::Expired,

                        actor: null,

                        reason: 'Reservation expired.',
                    );
            },
            attempts: 3,
        );
    }

    /**
     * Releases all active reservations before a configuration
     * is edited.
     */
    public function cancelActiveForConfiguration(
    Configuration $configuration,
    ?User $actor = null,
    string $reason = 'Configuration changed.',
    ): int {
    return DB::transaction(
        function () use (
            $configuration,
            $actor,
            $reason,
        ): int {
            $reservations =
                InventoryReservation::query()
                    ->where(
                        'configuration_id',
                        $configuration->getKey(),
                    )
                    ->where(
                        'status',
                        InventoryReservationStatus
                            ::Active
                            ->value,
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

            foreach ($reservations as $reservation) {
                $this->releaseLockedReservation(
                    reservation: $reservation,

                    terminalStatus:
                        InventoryReservationStatus
                            ::Cancelled,

                    actor: $actor,
                    reason: $reason,
                );
            }

            return $reservations->count();
        },
        attempts: 3,
    );
}

    /**
     * Consume means the units physically leave stock.
     */
    public function consume(InventoryReservation $reservation, ?User $actor = null, string $reason = 'Reserved stock consumed.'): InventoryReservation
    {
        return DB::transaction(function () use ($reservation, $actor, $reason): InventoryReservation {
            $lockedReservation =
                InventoryReservation::query()
                    ->whereKey($reservation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (
                $lockedReservation->status
                === InventoryReservationStatus::Consumed
            ) {
                return $this->loadReservation(
                    $lockedReservation,
                );
            }

            if (
                ! in_array(
                    $lockedReservation->status,
                    [
                        InventoryReservationStatus::Active,
                        InventoryReservationStatus::Committed,
                    ],
                    true,
                )
            ) {
                throw new DomainException(
                    'Only an active reservation can be consumed.',
                );
            }

            if (
                $lockedReservation->status
                    === InventoryReservationStatus::Active
                && $lockedReservation->isExpired()
            ) {
                return $this
                    ->releaseLockedReservation(
                        reservation: $lockedReservation,

                        terminalStatus: InventoryReservationStatus::Expired,

                        actor: $actor,

                        reason: 'Reservation expired before it could be consumed.',
                    );
            }

            [
                $reservationItems,
                $inventoryItems,
            ] = $this
                ->lockReservationStock(
                    $lockedReservation,
                );

            foreach (
                $reservationItems as $reservationItem
            ) {
                $remaining =
                    $reservationItem
                        ->outstandingQuantity();

                if ($remaining === 0) {
                    continue;
                }

                $inventoryItem =
                    $inventoryItems->get(
                        $reservationItem
                            ->inventory_item_id,
                    );

                if ($inventoryItem === null) {
                    throw new DomainException(
                        'A reservation inventory item could not be locked.',
                    );
                }

                $this->ledger
                    ->applyToLockedItem(
                        inventoryItem: $inventoryItem,

                        type: InventoryMovementType::ReservationConsumed,

                        onHandDelta: -$remaining,

                        reservedDelta: -$remaining,

                        reservation: $lockedReservation,

                        reservationItem: $reservationItem,

                        actor: $actor,

                        idempotencyKey: sprintf(
                            'reservation:%s:consume:%d',
                            $lockedReservation
                                ->public_id,
                            $reservationItem
                                ->id,
                        ),

                        reason: $reason,
                    );

                $reservationItem->forceFill([
                    'consumed_quantity' => $reservationItem
                        ->consumed_quantity
                        + $remaining,
                ])->save();
            }

            $lockedReservation->forceFill([
                'status' => InventoryReservationStatus::Consumed,

                'consumed_at' => now(),
            ])->save();

            return $this->loadReservation(
                $lockedReservation,
            );
        },
            attempts: 3,
        );
    }

    private function assertConfigurationCanReserve(
        Configuration $configuration,
    ): void {
        if (
            $configuration->status
            !== ConfigurationStatus::Valid
        ) {
            throw new DomainException(
                'Only a valid configuration can reserve inventory.',
            );
        }

        $validationRun =
            $configuration
                ->validationRuns()
                ->latest('id')
                ->first();

        if ($validationRun === null) {
            throw new DomainException(
                'The configuration has not been validated.',
            );
        }

        if (
            $validationRun
                ->configuration_version
            !== $configuration->version
        ) {
            throw new DomainException(
                'The latest validation belongs to an older configuration version.',
            );
        }

        if (
            ! in_array(
                $validationRun->status,
                [
                    ValidationRunStatus::Passed,
                    ValidationRunStatus::PassedWithWarnings,
                ],
                true,
            )
        ) {
            throw new DomainException(
                'The configuration did not pass validation.',
            );
        }
    }

    /**
     * @param  Collection<int, mixed>  $configurationItems
     * @return Collection<int, array{
     *     variant: ProductVariant,
     *     quantity: int
     * }>
     */
    private function buildRequirements(
        Collection $configurationItems,
    ): Collection {
        return $configurationItems
            ->groupBy('product_variant_id')
            ->map(
                static function (
                    Collection $items,
                ): array {
                    $first = $items->first();

                    return [
                        'variant' => $first->variant,

                        'quantity' => (int) $items
                            ->sum('quantity'),
                    ];
                },
            );
    }

    /**
     * @param Collection<int, array{
     *     variant: ProductVariant,
     *     quantity: int
     * }> $requirements
     * @return array{
     *     Warehouse,
     *     Collection<int, InventoryItem>
     * }
     */
    private function lockWarehouseWithStock(
        Collection $requirements,
    ): array {
        $variantIds = $requirements
            ->keys()
            ->map(
                static fn ($id): int => (int) $id,
            )
            ->values();

        $candidateIds = Warehouse::query()
            ->forAssembly()
            ->pluck('id');

        /** @var array<int, int> $bestAvailability */
        $bestAvailability = [];

        foreach ($candidateIds as $warehouseId) {
            /*
             * Every reservation process visits warehouses in
             * the same priority order and inventory rows in ID
             * order. Consistent lock ordering reduces deadlocks.
             */
            $warehouse = Warehouse::query()
                ->whereKey($warehouseId)
                ->lockForUpdate()
                ->first();

            if (
                $warehouse === null
                || ! $warehouse->is_active
                || ! $warehouse
                    ->can_assemble_systems
            ) {
                continue;
            }

            $inventoryItems =
                InventoryItem::query()
                    ->where(
                        'warehouse_id',
                        $warehouse->id,
                    )
                    ->active()
                    ->whereIn(
                        'product_variant_id',
                        $variantIds,
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(
                        'product_variant_id',
                    );

            $canFulfill = true;

            foreach (
                $requirements as $variantId => $requirement
            ) {
                $inventoryItem =
                    $inventoryItems->get(
                        (int) $variantId,
                    );

                $available =
                    $inventoryItem
                        ?->reservableQuantity()
                    ?? 0;

                $bestAvailability[
                    (int) $variantId
                ] = max(
                    $bestAvailability[
                        (int) $variantId
                    ] ?? 0,
                    $available,
                );

                if (
                    $available
                    < $requirement['quantity']
                ) {
                    $canFulfill = false;
                }
            }

            if ($canFulfill) {
                return [
                    $warehouse,
                    $inventoryItems,
                ];
            }
        }

        $shortages = [];

        foreach (
            $requirements as $variantId => $requirement
        ) {
            $available =
                $bestAvailability[
                    (int) $variantId
                ] ?? 0;

            if (
                $available
                >= $requirement['quantity']
            ) {
                continue;
            }

            $variant =
                $requirement['variant'];

            $shortages[] = [
                'product_variant_id' => $variant->id,

                'sku' => $variant->sku,

                'name' => $variant->displayName(),

                'required' => $requirement['quantity'],

                'best_available' => $available,
            ];
        }

        throw new InsufficientInventoryException(
            $shortages,
        );
    }

    private function terminateReservation(
        InventoryReservation $reservation,
        InventoryReservationStatus $terminalStatus,
        ?User $actor,
        string $reason,
    ): InventoryReservation {
        return DB::transaction(
            function () use (
                $reservation,
                $terminalStatus,
                $actor,
                $reason,
            ): InventoryReservation {
                $lockedReservation =
                    InventoryReservation::query()
                        ->whereKey($reservation->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedReservation->status
                    !== InventoryReservationStatus::Active
                ) {
                    return $this->loadReservation(
                        $lockedReservation,
                    );
                }

                return $this
                    ->releaseLockedReservation(
                        reservation: $lockedReservation,

                        terminalStatus: $terminalStatus,

                        actor: $actor,
                        reason: $reason,
                    );
            },
            attempts: 3,
        );
    }

    private function releaseLockedReservation(
        InventoryReservation $reservation,
        InventoryReservationStatus $terminalStatus,
        ?User $actor,
        string $reason,
    ): InventoryReservation {
        if (
            ! in_array(
                $terminalStatus,
                [
                    InventoryReservationStatus::Released,
                    InventoryReservationStatus::Expired,
                    InventoryReservationStatus::Cancelled,
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'The requested status does not release stock.',
            );
        }

        if (
            $reservation->status
            !== InventoryReservationStatus::Active
        ) {
            return $this->loadReservation(
                $reservation,
            );
        }

        [
            $reservationItems,
            $inventoryItems,
        ] = $this->lockReservationStock(
            $reservation,
        );

        $movementType = match ($terminalStatus) {
            InventoryReservationStatus::Released => InventoryMovementType::ReservationReleased,

            InventoryReservationStatus::Expired => InventoryMovementType::ReservationExpired,

            InventoryReservationStatus::Cancelled => InventoryMovementType::ReservationCancelled,

            default => throw new InvalidArgumentException(
                'Invalid reservation release status.',
            ),
        };

        foreach (
            $reservationItems as $reservationItem
        ) {
            $remaining =
                $reservationItem
                    ->outstandingQuantity();

            if ($remaining === 0) {
                continue;
            }

            $inventoryItem =
                $inventoryItems->get(
                    $reservationItem
                        ->inventory_item_id,
                );

            if ($inventoryItem === null) {
                throw new DomainException(
                    'A reservation inventory item could not be locked.',
                );
            }

            $this->ledger
                ->applyToLockedItem(
                    inventoryItem: $inventoryItem,

                    type: $movementType,

                    onHandDelta: 0,

                    reservedDelta: -$remaining,

                    reservation: $reservation,

                    reservationItem: $reservationItem,

                    actor: $actor,

                    idempotencyKey: sprintf(
                        'reservation:%s:%s:%d',
                        $reservation
                            ->public_id,
                        $terminalStatus
                            ->value,
                        $reservationItem
                            ->id,
                    ),

                    reason: $reason,
                );

            $reservationItem->forceFill([
                'released_quantity' => $reservationItem
                    ->released_quantity
                    + $remaining,
            ])->save();
        }

        $now = now();

        $attributes = [
            'status' => $terminalStatus,
            'released_at' => $now,
        ];

        if (
            $terminalStatus
            === InventoryReservationStatus::Expired
        ) {
            $attributes['expired_at'] = $now;
        }

        if (
            $terminalStatus
            === InventoryReservationStatus::Cancelled
        ) {
            $attributes['cancelled_at'] = $now;
        }

        $reservation
            ->forceFill($attributes)
            ->save();

        return $this->loadReservation(
            $reservation,
        );
    }

    /**
     * @return array{
     *     Collection<int, InventoryReservationItem>,
     *     Collection<int, InventoryItem>
     * }
     */
    private function lockReservationStock(
        InventoryReservation $reservation,
    ): array {
        $reservationItems =
            $reservation
                ->items()
                ->orderBy('inventory_item_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

        $inventoryItemIds =
            $reservationItems
                ->pluck('inventory_item_id')
                ->unique()
                ->sort()
                ->values();

        $inventoryItems =
            InventoryItem::query()
                ->whereIn(
                    'id',
                    $inventoryItemIds,
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

        return [
            $reservationItems,
            $inventoryItems,
        ];
    }

    private function loadReservation(
        InventoryReservation $reservation,
    ): InventoryReservation {
        return $reservation
            ->unsetRelations()
            ->load([
                'warehouse',

                'configuration',

                'items.inventoryItem.variant.product',
            ]);
    }

    public function attachToOrder(
        InventoryReservation $reservation,
        Order $order,
    ): InventoryReservation {
        return DB::transaction(
            function () use (
                $reservation,
                $order,
            ): InventoryReservation {
                $lockedReservation =
                    InventoryReservation::query()
                        ->whereKey($reservation->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedReservation->status
                    !== InventoryReservationStatus::Active
                ) {
                    throw new DomainException(
                        'Only an active reservation can be attached to an order.',
                    );
                }

                if ($lockedReservation->isExpired()) {
                    throw new DomainException(
                        'The inventory reservation has expired.',
                    );
                }

                if (
                    $lockedReservation->order_id !== null
                    && $lockedReservation->order_id
                        !== $order->id
                ) {
                    throw new DomainException(
                        'The reservation already belongs to another order.',
                    );
                }

                $lockedReservation->forceFill([
                    'order_id' => $order->id,
                ])->save();

                return $lockedReservation->fresh([
                    'order',
                    'items',
                ]);
            },
            attempts: 3,
        );
    }

    public function commitForOrder(
        Order $order,
    ): int {
        return DB::transaction(
            function () use ($order): int {
                $reservations =
                    InventoryReservation::query()
                        ->where('order_id', $order->id)
                        ->where(
                            'status',
                            InventoryReservationStatus::Active,
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                foreach ($reservations as $reservation) {
                    if ($reservation->isExpired()) {
                        throw new DomainException(
                            'An order reservation expired before payment was confirmed.',
                        );
                    }

                    $reservation->forceFill([
                        'status' => InventoryReservationStatus::Committed,

                        'committed_at' => now(),
                    ])->save();
                }

                return $reservations->count();
            },
            attempts: 3,
        );
    }
}
