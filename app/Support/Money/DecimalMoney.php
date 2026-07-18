<?php
declare(strict_types=1);
namespace App\Support\Money;

use InvalidArgumentException;

final class DecimalMoney{
    public static function toCents(string $value): int{
        $normalized = str_replace(',','.',trim($value));

        if(!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/',$normalized)){
            throw new InvalidArgumentException('The amount must contain no more than two decimal places.');
        }

        [$major, $minor] = array_pad(explode('.', $normalized, 2),2,'0');

        $minor = str_pad($minor,2,'0',STR_PAD_RIGHT);

        return ((int) $major * 100) + (int) $minor;
    }
}