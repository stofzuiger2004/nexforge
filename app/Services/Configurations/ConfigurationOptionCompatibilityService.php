<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\CompatibilitySeverity;
use App\Enums\ComponentSlot;
use App\Enums\ValidationResultStatus;
use App\Models\CompatibilityRule;
use App\Models\Configuration;
use App\Models\ConfigurationItem;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

final class ConfigurationOptionCompatibilityService
{
    public function __construct(
        private readonly CompatibilityRuleEvaluator $evaluator,
    ) {}

    /**
     * @param  Collection<int, CompatibilityRule>  $rules
     * @return array{
     *     status: string,
     *     has_errors: bool,
     *     messages: array<int, array{
     *         rule_key: string,
     *         severity: string,
     *         message: string
     *     }>
     * }
     */
    public function evaluate(
        Configuration $configuration,
        ComponentSlot $slot,
        ProductVariant $candidate,
        Collection $rules,
    ): array {
        $preview = $this->previewConfiguration(
            $configuration,
            $slot,
            $candidate,
        );

        $affectedRules = $rules->filter(
            static fn (
                CompatibilityRule $rule,
            ): bool => $rule->source_slot === $slot
                || $rule->target_slot === $slot,
        );

        $messages = [];

        foreach ($affectedRules as $rule) {
            $evaluation = $this->evaluator->evaluate(
                $preview,
                $rule,
            );

            if (
                $evaluation->status
                !== ValidationResultStatus::Failed
            ) {
                continue;
            }

            $messages[] = [
                'rule_key' => $rule->key,
                'severity' => $rule->severity->value,

                'message' => $evaluation->message
                    ?? $rule->failure_message,
            ];
        }

        $hasErrors = collect($messages)->contains(
            static fn (array $message): bool => $message['severity']
                === CompatibilitySeverity::Error->value,
        );

        $hasWarnings = collect($messages)->contains(
            static fn (array $message): bool => $message['severity']
                !== CompatibilitySeverity::Error->value,
        );

        return [
            'status' => match (true) {
                $hasErrors => 'requires_changes',
                $hasWarnings => 'warning',
                default => 'compatible',
            },

            'has_errors' => $hasErrors,
            'messages' => $messages,
        ];
    }

    private function previewConfiguration(
        Configuration $configuration,
        ComponentSlot $slot,
        ProductVariant $candidate,
    ): Configuration {
        $preview = clone $configuration;

        $previewItems = $configuration
            ->items
            ->map(
                function (
                    ConfigurationItem $item,
                ) use (
                    $slot,
                    $candidate,
                ): ConfigurationItem {
                    $previewItem = clone $item;

                    if ($previewItem->slot === $slot) {
                        $previewItem->product_variant_id =
                            $candidate->id;

                        $previewItem->sku_snapshot =
                            $candidate->sku;

                        $previewItem->name_snapshot =
                            $candidate->displayName();

                        $previewItem->setRelation(
                            'variant',
                            $candidate,
                        );
                    }

                    return $previewItem;
                },
            );

        $preview->setRelation(
            'items',
            $previewItems,
        );

        return $preview;
    }
}
