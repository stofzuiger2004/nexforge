<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderAdjustmentType: string
{
    case Package = 'package';
    case Discount = 'discount';
    case Fee = 'fee';
    case Shipping = 'shipping';
    case Tax = 'tax';
    case Manual = 'manual';
}
