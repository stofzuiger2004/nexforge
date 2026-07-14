<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Creating = 'creating';
    case Open = 'open';
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Error = 'error';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Paid,
            self::Failed,
            self::Cancelled,
            self::Expired => true,

            default => false,
        };
    }
}
