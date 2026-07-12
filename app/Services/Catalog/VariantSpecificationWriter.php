<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\SpecificationDataType;
use App\Models\ProductVariant;
use App\Models\ProductVariantSpecificationValue;
use App\Models\Specification;
use App\Models\SpecificationOption;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class VariantSpecificationWriter
{
    public function set(
        ProductVariant $variant,
        string $specificationKey,
        mixed $value,
    ): void {
        $specification = Specification::query()
            ->where('key', $specificationKey)
            ->firstOrFail();

        DB::transaction(function () use (
            $variant,
            $specification,
            $value,
        ): void {
            $this->clearExistingValue($variant, $specification);

            match ($specification->data_type) {
                SpecificationDataType::Text => $this->storeText(
                    $variant,
                    $specification,
                    $value,
                ),

                SpecificationDataType::Integer => $this->storeInteger(
                    $variant,
                    $specification,
                    $value,
                ),

                SpecificationDataType::Decimal => $this->storeDecimal(
                    $variant,
                    $specification,
                    $value,
                ),

                SpecificationDataType::Boolean => $this->storeBoolean(
                    $variant,
                    $specification,
                    $value,
                ),

                SpecificationDataType::Option => $this->storeOptions(
                    $variant,
                    $specification,
                    $value,
                    false,
                ),

                SpecificationDataType::MultiOption => $this->storeOptions(
                    $variant,
                    $specification,
                    $value,
                    true,
                ),
            };
        });
    }

    private function clearExistingValue(
        ProductVariant $variant,
        Specification $specification,
    ): void {
        $variant
            ->specificationValues()
            ->where('specification_id', $specification->id)
            ->delete();

        $optionIds = $specification
            ->options()
            ->pluck('id')
            ->all();

        if ($optionIds !== []) {
            $variant
                ->specificationOptions()
                ->detach($optionIds);
        }
    }

    private function storeText(
        ProductVariant $variant,
        Specification $specification,
        mixed $value,
    ): void {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(
                sprintf(
                    'Specification "%s" requires a non-empty string.',
                    $specification->key,
                ),
            );
        }

        $this->storeScalar($variant, $specification, [
            'value_text' => $value,
        ]);
    }

    private function storeInteger(
        ProductVariant $variant,
        Specification $specification,
        mixed $value,
    ): void {
        if (! is_int($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Specification "%s" requires an integer.',
                    $specification->key,
                ),
            );
        }

        $this->storeScalar($variant, $specification, [
            'value_integer' => $value,
        ]);
    }

    private function storeDecimal(
        ProductVariant $variant,
        Specification $specification,
        mixed $value,
    ): void {
        if (! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Specification "%s" requires a decimal number.',
                    $specification->key,
                ),
            );
        }

        $this->storeScalar($variant, $specification, [
            'value_decimal' => $value,
        ]);
    }

    private function storeBoolean(
        ProductVariant $variant,
        Specification $specification,
        mixed $value,
    ): void {
        if (! is_bool($value)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Specification "%s" requires a Boolean.',
                    $specification->key,
                ),
            );
        }

        $this->storeScalar($variant, $specification, [
            'value_boolean' => $value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function storeScalar(
        ProductVariant $variant,
        Specification $specification,
        array $value,
    ): void {
        ProductVariantSpecificationValue::query()->create([
            'product_variant_id' => $variant->id,
            'specification_id' => $specification->id,
            'value_text' => $value['value_text'] ?? null,
            'value_integer' => $value['value_integer'] ?? null,
            'value_decimal' => $value['value_decimal'] ?? null,
            'value_boolean' => $value['value_boolean'] ?? null,
        ]);
    }

    private function storeOptions(
        ProductVariant $variant,
        Specification $specification,
        mixed $value,
        bool $multiple,
    ): void {
        $requestedValues = is_array($value)
            ? $value
            : [$value];

        $requestedValues = array_values(array_unique(
            array_map(
                static fn (mixed $item): string => (string) $item,
                $requestedValues,
            ),
        ));

        if ($requestedValues === []) {
            throw new InvalidArgumentException(
                sprintf(
                    'Specification "%s" requires an option.',
                    $specification->key,
                ),
            );
        }

        if (! $multiple && count($requestedValues) !== 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Specification "%s" accepts exactly one option.',
                    $specification->key,
                ),
            );
        }

        $options = SpecificationOption::query()
            ->where('specification_id', $specification->id)
            ->whereIn('value', $requestedValues)
            ->get();

        if ($options->count() !== count($requestedValues)) {
            throw new InvalidArgumentException(
                sprintf(
                    'One or more options for specification "%s" do not exist.',
                    $specification->key,
                ),
            );
        }

        $variant
            ->specificationOptions()
            ->attach($options->modelKeys());
    }
}
