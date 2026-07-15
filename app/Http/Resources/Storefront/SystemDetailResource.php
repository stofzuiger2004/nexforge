<?php

declare(strict_types=1);

namespace App\Http\Resources\Storefront;

use App\Models\ProductVariant;
use App\Models\SystemComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\System
 */
class SystemDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = $this->prices->first();

        $rawAvailableBuilds = $this->resource
            ->getAttribute('available_builds');

        $availableBuilds = $rawAvailableBuilds === null
            ? null
            : (int) $rawAvailableBuilds;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,

            'short_description' =>
                $this->short_description,

            'description' =>
                $this->description,

            'is_configurable' =>
                $this->is_configurable,

            'images' => $this->images
                ->map(
                    fn ($image): array => [
                        'id' => $image->id,

                        'url' => Storage::disk(
                            $image->disk,
                        )->url($image->path),

                        'alt' =>
                            $image->alt_text
                            ?: $this->name,

                        'is_primary' =>
                            $image->is_primary,
                    ],
                )
                ->values()
                ->all(),

            'price' => $price === null
                ? null
                : [
                    'amount_in_cents' =>
                        $price->amount_in_cents,

                    'compare_at_amount_in_cents' =>
                        $price
                            ->compare_at_amount_in_cents,

                    'currency' =>
                        $price
                            ->priceList
                            ->currency,
                ],

            'availability' =>
                $this->availabilityData(
                    $availableBuilds,
                ),

            'components' => $this->components
                ->map(
                    fn (
                        SystemComponent $component,
                    ): array => $this->componentData(
                        $component,
                    ),
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     label: string,
     *     available_builds: int|null
     * }
     */
    private function availabilityData(
        ?int $availableBuilds,
    ): array {
        return match (true) {
            $availableBuilds === null => [
                'status' => 'made_to_order',
                'label' => 'Built to order',
                'available_builds' => null,
            ],

            $availableBuilds === 0 => [
                'status' => 'unavailable',
                'label' => 'Component stock unavailable',
                'available_builds' => 0,
            ],

            $availableBuilds <= 3 => [
                'status' => 'limited',
                'label' => 'Limited component stock',
                'available_builds' => $availableBuilds,
            ],

            default => [
                'status' => 'available',
                'label' => 'Available to configure',
                'available_builds' => $availableBuilds,
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function componentData(
        SystemComponent $component,
    ): array {
        $variant = $component->variant;
        $product = $variant->product;

        return [
            'id' => $component->id,

            'slot' =>
                $component->slot->value,

            'slot_label' =>
                $component->slot->label(),

            'quantity' =>
                $component->quantity,

            'is_required' =>
                $component->is_required,

            'is_replaceable' =>
                $component->is_replaceable,

            'name' =>
                $variant->displayName(),

            'brand' =>
                $product->brand?->name,

            'sku' =>
                $variant->sku,

            'product_slug' =>
                $product->slug,

            'category' =>
                $product->category->name,

            'specifications' =>
                $this->specificationData(
                    $variant,
                ),
        ];
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     value: string
     * }>
     */
    private function specificationData(
        ProductVariant $variant,
    ): array {
        $scalarSpecifications = $variant
            ->specificationValues
            ->map(
                function ($value): ?array {
                    $specification =
                        $value->specification;

                    $rawValue =
                        $this->scalarValue(
                            $value,
                        );

                    if ($rawValue === null) {
                        return null;
                    }

                    return [
                        'key' =>
                            $specification->key,

                        'label' =>
                            $specification->name,

                        'value' =>
                            $this->formatValue(
                                $rawValue,
                                $specification->unit,
                            ),

                        'sort_order' =>
                            $specification
                                ->sort_order,
                    ];
                },
            )
            ->filter();

        $optionSpecifications = $variant
            ->specificationOptions
            ->groupBy('specification_id')
            ->map(
                function (
                    Collection $options,
                ): array {
                    $firstOption =
                        $options->first();

                    $specification =
                        $firstOption
                            ->specification;

                    return [
                        'key' =>
                            $specification->key,

                        'label' =>
                            $specification->name,

                        'value' => $options
                            ->pluck('label')
                            ->implode(', '),

                        'sort_order' =>
                            $specification
                                ->sort_order,
                    ];
                },
            );

        return $scalarSpecifications
            ->concat($optionSpecifications)
            ->sortBy('sort_order')
            ->take(4)
            ->map(
                static fn (array $item): array => [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'value' => $item['value'],
                ],
            )
            ->values()
            ->all();
    }

    private function scalarValue(
        object $value,
    ): string|int|float|bool|null {
        foreach (
            [
                'value_text',
                'value_integer',
                'value_decimal',
                'value_boolean',
            ]
            as $column
        ) {
            $candidate =
                $value->getAttribute($column);

            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private function formatValue(
        string|int|float|bool $value,
        ?string $unit,
    ): string {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        $formattedValue = (string) $value;

        /*
         * Decimal values may be stored as strings such as
         * "120.0000". Remove unnecessary trailing zeroes.
         */
        if (
            is_numeric($value)
            && str_contains(
                $formattedValue,
                '.',
            )
        ) {
            $formattedValue = rtrim(
                rtrim(
                    $formattedValue,
                    '0',
                ),
                '.',
            );
        }

        if ($unit === null || $unit === '') {
            return $formattedValue;
        }

        return sprintf(
            '%s %s',
            $formattedValue,
            $unit,
        );
    }
}