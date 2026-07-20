<?php

declare(strict_types=1);

namespace App\Services\Systems;

use App\Enums\CompatibilitySeverity;
use App\Enums\ComponentSlot;
use App\Enums\ValidationResultStatus;
use App\Models\CompatibilityRule;
use App\Models\Configuration;
use App\Models\ConfigurationItem;
use App\Models\ProductVariant;
use App\Models\System;
use App\Models\SystemComponent;
use App\Services\Configurations\CompatibilityRuleEvaluator;
use DomainException;
use Illuminate\Support\Collection;

final class SystemPresetCompatibilityService
{
    public function __construct(
        private readonly CompatibilityRuleEvaluator $evaluator,
    ) {}

    /**
     * @return array{
     *     is_compatible: bool,
     *     summary: array{passed: int, failed: int, warnings: int, skipped: int},
     *     results: array<int, array<string, mixed>>
     * }
     */
    public function evaluate(
        System $system,
        ?ComponentSlot $replacementSlot = null,
        ?ProductVariant $replacementVariant = null,
        int $replacementQuantity = 1,
    ): array {
        $system->loadMissing([
            'components.variant.product',
            'components.variant.specificationValues',
            'components.variant.specificationOptions',
        ]);

        if ($replacementVariant !== null) {
            $replacementVariant->loadMissing([
                'product',
                'specificationValues',
                'specificationOptions',
            ]);
        }

        /** @var Collection<string, SystemComponent> $components */
        $components = $system
            ->components
            ->keyBy(
                static fn (SystemComponent $component): string => $component
                    ->slot
                    ->value,
            );

        if (
            $replacementSlot !== null
            && $replacementVariant !== null
        ) {
            $current = $components->get(
                $replacementSlot->value,
            );

            $replacement = new SystemComponent([
                'system_id' => $system->id,
                'product_variant_id' => $replacementVariant->id,
                'slot' => $replacementSlot,
                'quantity' => max(1, $replacementQuantity),
                'is_required' => $current?->is_required ?? true,
                'is_replaceable' => $current?->is_replaceable ?? true,
                'sort_order' => $replacementSlot->sortOrder(),
            ]);

            $replacement->setRelation(
                'variant',
                $replacementVariant,
            );

            $components->put(
                $replacementSlot->value,
                $replacement,
            );
        }

        $configuration = new Configuration();

        $items = $components
            ->map(
                static function (
                    SystemComponent $component,
                ): ConfigurationItem {
                    $item = new ConfigurationItem([
                        'product_variant_id' => $component
                            ->product_variant_id,
                        'source_system_component_id' => $component->id,
                        'slot' => $component->slot,
                        'quantity' => $component->quantity,
                        'sort_order' => $component->sort_order,
                        'sku_snapshot' => $component->variant->sku,
                        'name_snapshot' => $component
                            ->variant
                            ->displayName(),
                        'unit_price_in_cents' => 0,
                        'line_total_in_cents' => 0,
                    ]);

                    if ($component->exists) {
                        $item->setAttribute(
                            'id',
                            $component->id,
                        );
                    }

                    $item->setRelation(
                        'variant',
                        $component->variant,
                    );

                    return $item;
                },
            )
            ->values();

        $configuration->setRelation(
            'items',
            $items,
        );

        $rules = CompatibilityRule::query()
            ->active()
            ->with([
                'sourceSpecification',
                'targetSpecification',
            ])
            ->get();

        $passed = 0;
        $failed = 0;
        $warnings = 0;
        $skipped = 0;

        $results = $rules
            ->map(
                function (
                    CompatibilityRule $rule,
                ) use (
                    $configuration,
                    &$passed,
                    &$failed,
                    &$warnings,
                    &$skipped,
                ): array {
                    $evaluation = $this->evaluator->evaluate(
                        $configuration,
                        $rule,
                    );

                    match ($evaluation->status) {
                        ValidationResultStatus::Passed => $passed++,
                        ValidationResultStatus::Failed => $failed++,
                        ValidationResultStatus::Skipped => $skipped++,
                    };

                    if (
                        $evaluation->status
                            === ValidationResultStatus::Failed
                        && $rule->severity
                            !== CompatibilitySeverity::Error
                    ) {
                        $warnings++;
                    }

                    return [
                        'rule_key' => $rule->key,
                        'rule_name' => $rule->name,
                        'status' => $evaluation->status->value,
                        'severity' => $rule->severity->value,
                        'message' => $evaluation->message,
                        'source_slot' => $rule->source_slot->value,
                        'target_slot' => $rule->target_slot?->value,
                        'context' => $evaluation->context,
                    ];
                },
            )
            ->values()
            ->all();

        $hasBlockingFailure = collect($results)
            ->contains(
                static fn (array $result): bool => $result['status'] === ValidationResultStatus::Failed->value
                    && $result['severity'] === CompatibilitySeverity::Error->value,
            );

        return [
            'is_compatible' => ! $hasBlockingFailure,
            'summary' => [
                'passed' => $passed,
                'failed' => $failed,
                'warnings' => $warnings,
                'skipped' => $skipped,
            ],
            'results' => $results,
        ];
    }

    public function assertReplacementIsCompatible(
        System $system,
        ComponentSlot $slot,
        ProductVariant $variant,
        int $quantity = 1,
    ): void {
        $evaluation = $this->evaluate(
            system: $system,
            replacementSlot: $slot,
            replacementVariant: $variant,
            replacementQuantity: $quantity,
        );

        $blockingMessages = collect(
            $evaluation['results'],
        )
            ->filter(
                static function (
                    array $result,
                ) use ($slot): bool {
                    if (
                        $result['status']
                            !== ValidationResultStatus::Failed->value
                        || $result['severity']
                            !== CompatibilitySeverity::Error->value
                    ) {
                        return false;
                    }

                    if (
                        ($result['context']['reason'] ?? null)
                            === 'required_slot_missing'
                    ) {
                        return false;
                    }

                    return $result['source_slot'] === $slot->value
                        || $result['target_slot'] === $slot->value;
                },
            )
            ->map(
                static fn (array $result): string => (string) (
                    $result['message']
                    ?? $result['rule_name']
                ),
            )
            ->filter()
            ->unique()
            ->values();

        if ($blockingMessages->isNotEmpty()) {
            throw new DomainException(
                $blockingMessages->implode(' '),
            );
        }
    }
}
