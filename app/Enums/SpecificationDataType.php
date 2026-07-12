<?php

declare(strict_types=1);

namespace App\Enums;

enum SpecificationDataType: string
{
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Option = 'option';
    case MultiOption = 'multi_option';
}
