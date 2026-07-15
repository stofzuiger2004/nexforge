<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\ValidationRunStatus;
use App\Models\Configuration;
use App\Models\InventoryReservation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

final class ConfigurationReviewPageDataService
{
    /**
     * @return array<string, mixed>
     */
    public function build(
        Configuration $configuration,
        InventoryReservation $reservation,
        ?User $user,
    ): array {
        $priceListId =
            $configuration->price_list_id;

        $configuration->load([
            'priceList',

            'sourceSystem.images',

            'sourceSystem.prices' => static fn ($query) => $query->where(
                'price_list_id',
                $priceListId,
            ),

            'items' => static fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),

            'items.variant.product.brand',

            'adjustments',

            'validationRuns' => static fn ($query) => $query
                ->latest('id')
                ->limit(1),
        ]);

        $reservation->load([
            'warehouse',
        ]);

        $system =
            $configuration->sourceSystem;

        $systemImage =
            $system?->images->first();

        $basePrice =
            $system
                ?->prices
                ->first()
                ?->amount_in_cents
            ?? $configuration
                ->total_in_cents;

        $shippingInCents = (int) config(
            'checkout.shipping.flat_rate_in_cents',
            0,
        );

        $latestValidationRun =
            $configuration
                ->validationRuns
                ->first();

        $validationIsCurrent =
            $latestValidationRun !== null
            && $latestValidationRun
                ->configuration_version
                === $configuration->version
            && in_array(
                $latestValidationRun->status,
                [
                    ValidationRunStatus::Passed,
                    ValidationRunStatus::PassedWithWarnings,
                ],
                true,
            );

        [$firstName, $lastName] =
            $this->splitName(
                $user?->name,
            );

        $defaultCountry = strtoupper(
            (string) config(
                'checkout.default_country',
                'BE',
            ),
        );

        $countries = collect(
            config(
                'checkout.shipping.allowed_countries',
                [],
            ),
        )
            ->map(
                static fn (
                    string $label,
                    string $code,
                ): array => [
                    'code' => $code,
                    'label' => $label,
                ],
            )
            ->values()
            ->all();

        return [
            'review' => [
                'configuration' => [
                    'public_id' => $configuration->public_id,

                    'name' => $configuration->name
                        ?? $system?->name
                        ?? 'Custom Gaming PC',

                    'version' => $configuration->version,

                    'status' => $configuration
                        ->status
                        ->value,
                ],

                'system' => [
                    'name' => $system?->name
                        ?? 'Custom Gaming PC',

                    'slug' => $system?->slug,

                    'image' => $systemImage === null
                            ? null
                            : [
                                'url' => Storage::disk(
                                    $systemImage->disk,
                                )->url(
                                    $systemImage->path,
                                ),

                                'alt' => $systemImage
                                    ->alt_text
                                    ?: $system?->name,
                            ],
                ],

                'components' => $configuration
                    ->items
                    ->map(
                        static fn ($item): array => [
                            'id' => $item->id,

                            'slot' => $item
                                ->slot
                                ->value,

                            'slot_label' => $item
                                ->slot
                                ->label(),

                            'name' => $item
                                ->name_snapshot,

                            'brand' => $item
                                ->variant
                                ->product
                                ->brand
                                ?->name,

                            'sku' => $item
                                ->sku_snapshot,

                            'quantity' => $item->quantity,

                            'unit_price_in_cents' => $item
                                ->unit_price_in_cents,

                            'line_total_in_cents' => $item
                                ->line_total_in_cents,
                        ],
                    )
                    ->values()
                    ->all(),

                'pricing' => [
                    'currency' => $configuration->currency,

                    'base_price_in_cents' => $basePrice,

                    'component_change_in_cents' => $configuration
                        ->total_in_cents
                        - $basePrice,

                    'configuration_total_in_cents' => $configuration
                        ->total_in_cents,

                    'shipping_in_cents' => $shippingInCents,

                    'order_total_in_cents' => $configuration
                        ->total_in_cents
                        + $shippingInCents,

                    'prices_include_tax' => $configuration
                        ->priceList
                        ->prices_include_tax,
                ],

                'reservation' => [
                    'public_id' => $reservation->public_id,

                    'status' => $reservation
                        ->status
                        ->value,

                    'reserved_at' => $reservation
                        ->reserved_at
                        ->toIso8601String(),

                    'expires_at' => $reservation
                        ->expires_at
                        ->toIso8601String(),

                    'is_expired' => $reservation
                        ->isExpired(),

                    'warehouse' => $reservation
                        ->warehouse
                        ->name,
                ],

                'validation' => [
                    'is_current' => $validationIsCurrent,

                    'label' => $validationIsCurrent
                            ? 'Configuration compatible'
                            : 'Validation required',
                ],

                'can_submit' => $validationIsCurrent
                    && ! $reservation->isExpired(),
            ],

            'checkout_defaults' => [
                'email' => $user?->email ?? '',

                'phone' => '',

                'shipping' => [
                    'first_name' => $firstName,

                    'last_name' => $lastName,

                    'company' => '',
                    'vat_number' => '',
                    'address_line_1' => '',
                    'address_line_2' => '',
                    'postal_code' => '',
                    'city' => '',
                    'state' => '',

                    'country_code' => $defaultCountry,
                ],

                'billing_same_as_shipping' => true,

                'billing' => [
                    'first_name' => $firstName,

                    'last_name' => $lastName,

                    'company' => '',
                    'vat_number' => '',
                    'address_line_1' => '',
                    'address_line_2' => '',
                    'postal_code' => '',
                    'city' => '',
                    'state' => '',

                    'country_code' => $defaultCountry,
                ],

                'terms' => false,
            ],

            'countries' => $countries,
        ];
    }

    /**
     * @return array{string, string}
     */
    private function splitName(
        ?string $name,
    ): array {
        $name = trim((string) $name);

        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split(
            '/\s+/',
            $name,
        ) ?: [];

        $firstName = array_shift($parts)
            ?? '';

        $lastName = implode(
            ' ',
            $parts,
        );

        return [
            $firstName,
            $lastName,
        ];
    }
}
