<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatusCategory: string
{
    case Order = 'order';
    case Payment = 'payment';
    case Fulfillment = 'fulfillment';
}
