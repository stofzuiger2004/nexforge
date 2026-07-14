<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderFulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case StockAllocated = 'stock_allocated';
    case Assembling = 'assembling';
    case ReadyToShip = 'ready_to_ship';
    case Fulfilled = 'fulfilled';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
