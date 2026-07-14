<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryReservationStatus: string
{
    case Active = 'active';

    /*
     * Payment succeeded and the reservation now belongs
     * to a confirmed order. It must no longer expire.
     */
    case Committed = 'committed';

    case Released = 'released';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Consumed = 'consumed';

    public function holdsStock(): bool
    {
        return match ($this) {
            self::Active,
            self::Committed => true,

            default => false,
        };
    }

    public function canExpire(): bool
    {
        return $this === self::Active;
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Released,
            self::Expired,
            self::Cancelled,
            self::Consumed => true,

            self::Active,
            self::Committed => false,
        };
    }
}
