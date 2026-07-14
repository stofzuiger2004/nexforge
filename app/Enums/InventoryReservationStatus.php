<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryReservationStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Consumed = 'consumed';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isTerminal(): bool
    {
        return $this !== self::Active;
    }
}
