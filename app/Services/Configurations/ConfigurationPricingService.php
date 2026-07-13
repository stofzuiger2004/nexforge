<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Models\Configuration;
use DomainException;

final class ConfigurationPricingService
{
    public function recalculate(
        Configuration $configuration,
    ): Configuration {
        $subtotal = (int) $configuration
            ->items()
            ->sum('line_total_in_cents');

        $adjustmentTotal = (int) $configuration
            ->adjustments()
            ->sum('amount_in_cents');

        $total = $subtotal
            + $adjustmentTotal
            + $configuration->tax_in_cents;

        if ($total < 0) {
            throw new DomainException(
                'Configuration adjustments may not produce a negative total.',
            );
        }

        $configuration->forceFill([
            'subtotal_in_cents' => $subtotal,

            'adjustment_total_in_cents' => $adjustmentTotal,

            'total_in_cents' => $total,
            'priced_at' => now(),
        ])->save();

        return $configuration;
    }
}
