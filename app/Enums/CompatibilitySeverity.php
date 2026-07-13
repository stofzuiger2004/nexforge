<?php

declare(strict_types=1);

namespace App\Enums;

enum CompatibilitySeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
