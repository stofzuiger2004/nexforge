<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Models\Configuration;

final readonly class CreatedConfiguration
{
    public function __construct(
        public Configuration $configuration,
        public ?string $guestToken,
    ) {}
}
