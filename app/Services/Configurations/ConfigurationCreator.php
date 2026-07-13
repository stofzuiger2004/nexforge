<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\ConfigurationAdjustmentType;
use App\Enums\ConfigurationStatus;
use App\Models\Configuration;
use App\Models\PriceList;
use App\Models\System;
use App\Models\SystemPrice;
use App\Models\User;
use App\Models\VariantPrice;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConfigurationCreator
{
    public function createFromSystem(
        System $system,
        PriceList $priceList,
        ?User $user = null,
    ): CreatedConfiguration {
        return DB::transaction(
            function () use (
                $system,
                $priceList,
                $user,
            ): CreatedConfiguration {
                $systemIsPublished = System::query()
                    ->published()
                    ->whereKey($system->getKey())
                    ->exists();

                if (! $systemIsPublished) {
                    throw new DomainException(
                        'The selected system is not available for configuration.',
                    );
                }

                $priceListIsAvailable =
                    PriceList::query()
                        ->available()
                        ->whereKey(
                            $priceList->getKey(),
                        )
                        ->exists();

                if (! $priceListIsAvailable) {
                    throw new DomainException(
                        'The selected price list is not currently available.',
                    );
                }

                $system->loadMissing([
                    'components.variant.product',
                ]);

                if ($system->components->isEmpty()) {
                    throw new DomainException(
                        'The selected system does not contain any components.',
                    );
                }

                $variantIds = $system
                    ->components
                    ->pluck('product_variant_id')
                    ->unique()
                    ->values();

                $variantPrices =
                    VariantPrice::query()
                        ->where(
                            'price_list_id',
                            $priceList->id,
                        )
                        ->whereIn(
                            'product_variant_id',
                            $variantIds,
                        )
                        ->get()
                        ->keyBy('product_variant_id');

                $missingPriceSkus = $system
                    ->components
                    ->filter(
                        static fn ($component): bool => ! $variantPrices->has(
                            $component
                                ->product_variant_id,
                        ),
                    )
                    ->map(
                        static fn ($component): string => $component->variant->sku,
                    )
                    ->unique()
                    ->values();

                if ($missingPriceSkus->isNotEmpty()) {
                    throw new DomainException(sprintf(
                        'Missing variant prices for: %s.',
                        $missingPriceSkus->implode(', '),
                    ));
                }

                $systemPrice = SystemPrice::query()
                    ->where(
                        'system_id',
                        $system->id,
                    )
                    ->where(
                        'price_list_id',
                        $priceList->id,
                    )
                    ->first();

                if ($systemPrice === null) {
                    throw new DomainException(
                        'The selected system has no price in this price list.',
                    );
                }

                /*
                 * The raw token is returned once and should be
                 * stored in the browser session. Only its hash
                 * is stored in the database.
                 */
                $guestToken = $user === null
                    ? Str::random(64)
                    : null;

                $configuration =
                    Configuration::query()->create([
                        'user_id' => $user?->id,

                        'source_system_id' => $system->id,

                        'price_list_id' => $priceList->id,

                        'guest_token_hash' => $guestToken === null
                                ? null
                                : hash(
                                    'sha256',
                                    $guestToken,
                                ),

                        'name' => $system->name,

                        'status' => ConfigurationStatus::Draft,

                        'currency' => $priceList->currency,

                        'version' => 1,

                        'subtotal_in_cents' => 0,

                        'adjustment_total_in_cents' => 0,

                        'tax_in_cents' => 0,
                        'total_in_cents' => 0,

                        'priced_at' => now(),

                        'validated_at' => null,

                        'expires_at' => now()->addDays(30),

                        'last_activity_at' => now(),

                        'metadata' => [
                            'source_system_sku' => $system->sku,
                        ],
                    ]);

                $subtotal = 0;

                foreach (
                    $system->components as $component
                ) {
                    $variantPrice =
                        $variantPrices->get(
                            $component
                                ->product_variant_id,
                        );

                    $unitPrice =
                        $variantPrice
                            ->amount_in_cents;

                    $lineTotal =
                        $unitPrice
                        * $component->quantity;

                    $subtotal += $lineTotal;

                    $configuration
                        ->items()
                        ->create([
                            'product_variant_id' => $component
                                ->product_variant_id,

                            'source_system_component_id' => $component->id,

                            'slot' => $component->slot,

                            'quantity' => $component->quantity,

                            'sort_order' => $component->sort_order,

                            'sku_snapshot' => $component
                                ->variant
                                ->sku,

                            'name_snapshot' => $component
                                ->variant
                                ->displayName(),

                            'unit_price_in_cents' => $unitPrice,

                            'line_total_in_cents' => $lineTotal,

                            'metadata' => null,
                        ]);
                }

                $packageAdjustment =
                    $systemPrice->amount_in_cents
                    - $subtotal;

                if ($packageAdjustment !== 0) {
                    $configuration
                        ->adjustments()
                        ->create([
                            'type' => ConfigurationAdjustmentType::Package,

                            'code' => 'system-package-adjustment',

                            'label' => 'System package adjustment',

                            'amount_in_cents' => $packageAdjustment,

                            'is_taxable' => true,
                            'sort_order' => 10,

                            'metadata' => [
                                'source_system_id' => $system->id,
                            ],
                        ]);
                }

                $configuration->forceFill([
                    'subtotal_in_cents' => $subtotal,

                    'adjustment_total_in_cents' => $packageAdjustment,

                    'total_in_cents' => $systemPrice
                        ->amount_in_cents,
                ])->save();

                return new CreatedConfiguration(
                    $configuration->fresh([
                        'items.variant.product',
                        'adjustments',
                    ]),
                    $guestToken,
                );
            },
            attempts: 3,
        );
    }
}
