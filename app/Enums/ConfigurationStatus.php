<?php

declare(strict_types=1);

namespace App\Enums;

enum ConfigurationStatus: string
{
    case Draft = 'draft';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Converted = 'converted';
    case Expired = 'expired';

    public function isEditable(): bool
    {
        return match ($this) {
            self::Draft,
            self::Valid,
            self::Invalid => true,

            self::Converted,
            self::Expired => false,
        };
    }
}
