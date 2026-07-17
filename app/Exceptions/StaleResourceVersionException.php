<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

final class StaleResourceVersionException extends DomainException{
    public static function forInventoryItem(): self{
        return new self('This inventory item changed after you opened the page. Refresh and try again.');
    }

    public static function forVariantPrice(): self{
        return new self('This price changed after you opened the page. Refresh and try again.');
    }
}