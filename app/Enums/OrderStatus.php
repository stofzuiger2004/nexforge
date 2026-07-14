<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Assembling = 'assembling';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case ManualReview = 'manual_review';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed,
            self::Cancelled => true,

            default => false,
        };
    }
}
