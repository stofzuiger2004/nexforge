<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

final class InsufficientInventoryException extends DomainException
{
    /**
     * @param array<int, array{
     *     product_variant_id: int,
     *     sku: string,
     *     name: string,
     *     required: int,
     *     best_available: int
     * }> $shortages
     */
    public function __construct(public readonly array $shortages)
    {
        parent::__construct('No assembly warehouse has sufficient stock for this configuration.');
    }
}
