<?php

declare(strict_types=1);

namespace App\Services\Storefront;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class VariantPresentationService
{
    /**
     * @return array{
     *     url: string,
     *     alt: string
     * }|null
     */
    public function image(
        ProductVariant $variant,
    ): ?array {
        $image =
            $variant->images->first()
            ?? $variant->product->images->first();

        if ($image === null) {
            return null;
        }

        return [
            'url' => Storage::disk(
                $image->disk,
            )->url($image->path),

            'alt' => $image->alt_text
                ?: $variant->displayName(),
        ];
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     value: string
     * }>
     */
    public function specifications(
        ProductVariant $variant,
        int $limit = 4,
    ): array {
        $scalarSpecifications = $variant
            ->specificationValues
            ->map(
                function ($value): ?array {
                    $specification =
                        $value->specification;

                    $rawValue =
                        $this->scalarValue($value);

                    if ($rawValue === null) {
                        return null;
                    }

                    return [
                        'key' => $specification->key,

                        'label' => $specification->name,

                        'value' => $this->formatValue(
                            $rawValue,
                            $specification->unit,
                        ),

                        'sort_order' => $specification->sort_order,
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
                    $first = $options->first();
                    $specification =
                        $first->specification;

                    return [
                        'key' => $specification->key,

                        'label' => $specification->name,

                        'value' => $options
                            ->pluck('label')
                            ->implode(', '),

                        'sort_order' => $specification->sort_order,
                    ];
                },
            );

        return $scalarSpecifications
            ->concat($optionSpecifications)
            ->sortBy('sort_order')
            ->take($limit)
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
            ] as $column
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
