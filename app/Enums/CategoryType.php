<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case Component = 'component';
    case System = 'system';
    case Accessory = 'accessory';
    case Service = 'service';
}
