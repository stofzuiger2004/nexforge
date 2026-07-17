<?php

declare(strict_types=1);

use App\Enums\InventoryReservationStatus;
use App\Models\InventoryReservation;
use Carbon\CarbonInterface;

test('committed reservations hold stock and cast their committed timestamp', function (): void {
    $reservation = new InventoryReservation([
        'status' => InventoryReservationStatus::Committed,
        'committed_at' => '2026-07-16 12:00:00',
    ]);

    $bindings = InventoryReservation::query()
        ->holdingStock()
        ->getBindings();

    expect($reservation->status)
        ->toBe(InventoryReservationStatus::Committed)
        ->and($reservation->committed_at)
        ->toBeInstanceOf(CarbonInterface::class)
        ->and($bindings)
        ->toContain(
            InventoryReservationStatus::Active->value,
            InventoryReservationStatus::Committed->value,
        );
});
