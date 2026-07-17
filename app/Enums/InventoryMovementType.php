<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryMovementType: string
{
    case Receipt = 'receipt';
    case Adjustment = 'adjustment';

    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';

    case ReservationCreated = 'reservation_created';
    case ReservationReleased = 'reservation_released';
    case ReservationExpired = 'reservation_expired';
    case ReservationCancelled = 'reservation_cancelled';
    case ReservationConsumed = 'reservation_consumed';

    case CustomerReturn = 'customer_return';
    case Damaged = 'damaged';
    case StockCount = 'stock_count';
}
