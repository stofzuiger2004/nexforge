<?php

declare(strict_types=1);

use App\Models\Configuration;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\User;

test('user configuration and inventory relations resolve valid models', function (): void {
    $user = new User();

    expect($user->configurations()->getRelated())
        ->toBeInstanceOf(Configuration::class)
        ->and($user->createdInventoryReservations()->getRelated())
        ->toBeInstanceOf(InventoryReservation::class)
        ->and($user->inventoryMovements()->getRelated())
        ->toBeInstanceOf(InventoryMovement::class);
});
