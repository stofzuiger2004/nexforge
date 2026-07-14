<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderAddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
}
