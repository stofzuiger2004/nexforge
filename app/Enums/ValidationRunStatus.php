<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationRunStatus: string
{
    case Running = 'running';
    case Passed = 'passed';
    case PassedWithWarnings = 'passed_with_warnings';
    case Failed = 'failed';
    case Error = 'error';
}
