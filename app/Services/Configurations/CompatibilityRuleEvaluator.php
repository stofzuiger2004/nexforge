<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\CompatibilityRuleType;
use App\Enums\ComponentSlot;
use App\Models\CompatibilityRule;
use App\Models\Configuration;
use App\Models\ConfigurationItem;
use App\Models\ProductVariant;
use App\Models\Specification;

final class CompatibilityRuleEvaluator
{
    public function evaluate(
        Configuration $configuration,
        CompatibilityRule $rule,
    ): RuleEvaluation {
        return match ($rule->rule_type) {
            CompatibilityRuleType::RequiredSlot => $this->evaluateRequiredSlot(
                $configuration,
                $rule,
            ),

            CompatibilityRuleType::SameOption => $this->evaluateSameOption(
                $configuration,
                $rule,
            ),

            CompatibilityRuleType::OptionContainedInTarget => $this->evaluateOptionContainedInTarget(
                $configuration,
                $rule,
            ),

            CompatibilityRuleType::NumericLessThanOrEqual => $this->evaluateNumericLessThanOrEqual(
                $configuration,
                $rule,
            ),

            CompatibilityRuleType::Custom => RuleEvaluation::skipped(
                'This custom rule has no evaluator yet.',
                [
                    'reason' => 'custom_rule_not_implemented',
                ],
            ),
        };
    }

    private function evaluateRequiredSlot(
        Configuration $configuration,
        CompatibilityRule $rule,
    ): RuleEvaluation {
        $item = $this->itemForSlot(
            $configuration,
            $rule->source_slot,
        );

        if (
            $item !== null
            && $item->quantity > 0
        ) {
            return RuleEvaluation::passed([
                'source_slot' => $rule->source_slot->value,

                'source_item_id' => $item->id,

                'source_variant_id' => $item->product_variant_id,
            ]);
        }

        return RuleEvaluation::failed(
            $rule->failure_message,
            [
                'reason' => 'required_slot_missing',

                'source_slot' => $rule->source_slot->value,
            ],
        );
    }

    private function evaluateSameOption(
        Configuration $configuration,
        CompatibilityRule $rule,
    ): RuleEvaluation {
        $pair = $this->resolvePair(
            $configuration,
            $rule,
        );

        if ($pair instanceof RuleEvaluation) {
            return $pair;
        }

        [$sourceItem, $targetItem] = $pair;

        $sourceOptions = $this->optionValues(
            $sourceItem->variant,
            $rule->sourceSpecification,
        );

        $targetOptions = $this->optionValues(
            $targetItem->variant,
            $rule->targetSpecification,
        );

        if (
            $sourceOptions === []
            || $targetOptions === []
        ) {
            return $this->missingSpecificationData(
                $rule,
                $sourceItem,
                $targetItem,
            );
        }

        $compatible = array_intersect(
            array_keys($sourceOptions),
            array_keys($targetOptions),
        ) !== [];

        $context = $this->pairContext(
            $rule,
            $sourceItem,
            $targetItem,
            implode(
                ', ',
                array_values($sourceOptions),
            ),
            implode(
                ', ',
                array_values($targetOptions),
            ),
        );

        return $compatible
            ? RuleEvaluation::passed($context)
            : RuleEvaluation::failed(
                $this->renderFailureMessage(
                    $rule,
                    $context,
                ),
                $context,
            );
    }

    private function evaluateOptionContainedInTarget(
        Configuration $configuration,
        CompatibilityRule $rule,
    ): RuleEvaluation {
        $pair = $this->resolvePair(
            $configuration,
            $rule,
        );

        if ($pair instanceof RuleEvaluation) {
            return $pair;
        }

        [$sourceItem, $targetItem] = $pair;

        $sourceOptions = $this->optionValues(
            $sourceItem->variant,
            $rule->sourceSpecification,
        );

        $targetOptions = $this->optionValues(
            $targetItem->variant,
            $rule->targetSpecification,
        );

        if (
            $sourceOptions === []
            || $targetOptions === []
        ) {
            return $this->missingSpecificationData(
                $rule,
                $sourceItem,
                $targetItem,
            );
        }

        $compatible = array_diff(
            array_keys($sourceOptions),
            array_keys($targetOptions),
        ) === [];

        $context = $this->pairContext(
            $rule,
            $sourceItem,
            $targetItem,
            implode(
                ', ',
                array_values($sourceOptions),
            ),
            implode(
                ', ',
                array_values($targetOptions),
            ),
        );

        return $compatible
            ? RuleEvaluation::passed($context)
            : RuleEvaluation::failed(
                $this->renderFailureMessage(
                    $rule,
                    $context,
                ),
                $context,
            );
    }

    private function evaluateNumericLessThanOrEqual(
        Configuration $configuration,
        CompatibilityRule $rule,
    ): RuleEvaluation {
        $pair = $this->resolvePair(
            $configuration,
            $rule,
        );

        if ($pair instanceof RuleEvaluation) {
            return $pair;
        }

        [$sourceItem, $targetItem] = $pair;

        $sourceValue = $this->scalarValue(
            $sourceItem->variant,
            $rule->sourceSpecification,
        );

        $targetValue = $this->scalarValue(
            $targetItem->variant,
            $rule->targetSpecification,
        );

        if (
            ! is_numeric($sourceValue)
            || ! is_numeric($targetValue)
        ) {
            return $this->missingSpecificationData(
                $rule,
                $sourceItem,
                $targetItem,
            );
        }

        $compatible =
            (float) $sourceValue
            <= (float) $targetValue;

        $context = $this->pairContext(
            $rule,
            $sourceItem,
            $targetItem,
            $sourceValue,
            $targetValue,
        );

        return $compatible
            ? RuleEvaluation::passed($context)
            : RuleEvaluation::failed(
                $this->renderFailureMessage(
                    $rule,
                    $context,
                ),
                $context,
            );
    }

    /**
     * @return array{
     *     ConfigurationItem,
     *     ConfigurationItem
     * }|RuleEvaluation
     */
    private function resolvePair(
        Configuration $configuration,
        CompatibilityRule $rule,
    ): array|RuleEvaluation {
        if ($rule->target_slot === null) {
            return RuleEvaluation::skipped(
                'The rule does not define a target slot.',
                [
                    'reason' => 'target_slot_missing',
                ],
            );
        }

        $sourceItem = $this->itemForSlot(
            $configuration,
            $rule->source_slot,
        );

        $targetItem = $this->itemForSlot(
            $configuration,
            $rule->target_slot,
        );

        if (
            $sourceItem === null
            || $targetItem === null
        ) {
            /*
             * Missing required items are reported by
             * the RequiredSlot rules.
             */
            return RuleEvaluation::skipped(
                null,
                [
                    'reason' => 'source_or_target_item_missing',

                    'source_slot' => $rule
                        ->source_slot
                        ->value,

                    'target_slot' => $rule
                        ->target_slot
                        ->value,
                ],
            );
        }

        return [
            $sourceItem,
            $targetItem,
        ];
    }

    private function itemForSlot(
        Configuration $configuration,
        ComponentSlot $slot,
    ): ?ConfigurationItem {
        /** @var ConfigurationItem|null $item */
        $item = $configuration
            ->items
            ->first(
                static fn (
                    ConfigurationItem $item,
                ): bool => $item->slot === $slot,
            );

        return $item;
    }

    /**
     * @return array<string, string>
     */
    private function optionValues(
        ProductVariant $variant,
        ?Specification $specification,
    ): array {
        if ($specification === null) {
            return [];
        }

        return $variant
            ->specificationOptions
            ->where(
                'specification_id',
                $specification->id,
            )
            ->mapWithKeys(
                static fn ($option): array => [
                    (string) $option->value => (string) $option->label,
                ],
            )
            ->all();
    }

    private function scalarValue(
        ProductVariant $variant,
        ?Specification $specification,
    ): string|int|float|bool|null {
        if ($specification === null) {
            return null;
        }

        $value = $variant
            ->specificationValues
            ->firstWhere(
                'specification_id',
                $specification->id,
            );

        if ($value === null) {
            return null;
        }

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

    private function missingSpecificationData(
        CompatibilityRule $rule,
        ConfigurationItem $sourceItem,
        ConfigurationItem $targetItem,
    ): RuleEvaluation {
        /*
         * Fail closed: if technical data is missing, do not
         * tell the customer that compatibility is guaranteed.
         */
        return RuleEvaluation::failed(
            'Compatibility could not be verified because technical data is missing.',
            [
                'reason' => 'specification_data_missing',

                'rule_key' => $rule->key,

                'source_item_id' => $sourceItem->id,

                'target_item_id' => $targetItem->id,

                'source_specification_id' => $rule
                    ->source_specification_id,

                'target_specification_id' => $rule
                    ->target_specification_id,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function pairContext(
        CompatibilityRule $rule,
        ConfigurationItem $sourceItem,
        ConfigurationItem $targetItem,
        mixed $sourceValue,
        mixed $targetValue,
    ): array {
        return [
            'source_slot' => $rule->source_slot->value,

            'target_slot' => $rule->target_slot?->value,

            'source_item_id' => $sourceItem->id,

            'target_item_id' => $targetItem->id,

            'source_variant_id' => $sourceItem
                ->product_variant_id,

            'target_variant_id' => $targetItem
                ->product_variant_id,

            'source_name' => $sourceItem->name_snapshot,

            'target_name' => $targetItem->name_snapshot,

            'source_value' => $sourceValue,
            'target_value' => $targetValue,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function renderFailureMessage(
        CompatibilityRule $rule,
        array $context,
    ): string {
        return strtr(
            $rule->failure_message,
            [
                '{source_name}' => (string) (
                    $context['source_name']
                    ?? ''
                ),

                '{target_name}' => (string) (
                    $context['target_name']
                    ?? ''
                ),

                '{source_value}' => (string) (
                    $context['source_value']
                    ?? ''
                ),

                '{target_value}' => (string) (
                    $context['target_value']
                    ?? ''
                ),
            ],
        );
    }
}
