<?php

declare(strict_types=1);

namespace App\Enums;

enum CompatibilityRuleType: string
{
    case RequiredSlot = 'required_slot';
    case SameOption = 'same_option';
    case OptionContainedInTarget = 'option_contained_in_target';
    case NumericLessThanOrEqual = 'numeric_lte';
    case Custom = 'custom';
}
