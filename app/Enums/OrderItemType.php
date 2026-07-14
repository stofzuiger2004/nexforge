<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderItemType: string
{
    case ConfiguredSystem = 'configured_system';
    case Product = 'product';
    case Accessory = 'accessory';
    case Service = 'service';
}
