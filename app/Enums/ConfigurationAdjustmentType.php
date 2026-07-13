<?php

declare(strict_types=1);

namespace App\Enums;

enum ConfigurationAdjustmentType: string
{
    case Package = 'package';
    case Fee = 'fee';
    case Discount = 'discount';
    case Manual = 'manual';
}
