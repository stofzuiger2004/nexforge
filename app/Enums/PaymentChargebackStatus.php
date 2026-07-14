<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentChargebackStatus: string
{
    case Received = 'received';
    case Reversed = 'reversed';
}
