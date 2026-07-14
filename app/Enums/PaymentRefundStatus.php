<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentRefundStatus: string
{
    case Creating = 'creating';
    case Queued = 'queued';
    case Pending = 'pending';
    case Processing = 'processing';
    case Refunded = 'refunded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
