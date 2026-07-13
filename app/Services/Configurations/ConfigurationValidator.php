<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\CompatibilitySeverity;
use App\Enums\ConfigurationStatus;
use App\Enums\ValidationResultStatus;
use App\Enums\ValidationRunStatus;
use App\Models\CompatibilityRule;
use App\Models\Configuration;
use App\Models\ConfigurationValidationRun;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ConfigurationValidator
{
    public function __construct(
        private readonly CompatibilityRuleEvaluator $evaluator,
    ) {}

    public function validate(
        Configuration $configuration,
    ): ConfigurationValidationRun {
        return DB::transaction(
            function () use (
                $configuration,
            ): ConfigurationValidationRun {
                /*
                 * Prevent another request from modifying the
                 * configuration while it is being validated.
                 */
                $lockedConfiguration =
                    Configuration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $configuration->getKey(),
                        );

                if (
                    ! $lockedConfiguration
                        ->status
                        ->isEditable()
                ) {
                    throw new DomainException(
                        'This configuration can no longer be validated.',
                    );
                }

                $lockedConfiguration->load([
                    'items.variant.product',

                    'items.variant'
                        .'.specificationValues',

                    'items.variant'
                        .'.specificationOptions',
                ]);

                $rules = CompatibilityRule::query()
                    ->active()
                    ->with([
                        'sourceSpecification',
                        'targetSpecification',
                    ])
                    ->get();

                $run = $lockedConfiguration
                    ->validationRuns()
                    ->create([
                        'status' => ValidationRunStatus::Running,

                        'configuration_version' => $lockedConfiguration
                            ->version,

                        'rules_count' => $rules->count(),

                        'started_at' => now(),
                    ]);

                $passedCount = 0;
                $failedCount = 0;
                $warningCount = 0;
                $skippedCount = 0;
                $failedErrorCount = 0;

                foreach ($rules as $rule) {
                    $evaluation =
                        $this->evaluator->evaluate(
                            $lockedConfiguration,
                            $rule,
                        );

                    $run->results()->create([
                        'compatibility_rule_id' => $rule->id,

                        'rule_key' => $rule->key,

                        'rule_name' => $rule->name,

                        'rule_revision' => $rule->revision,

                        'status' => $evaluation->status,

                        'severity' => $rule->severity,

                        'message' => $evaluation->message,

                        'context' => $evaluation->context,
                    ]);

                    match ($evaluation->status) {
                        ValidationResultStatus::Passed => $passedCount++,

                        ValidationResultStatus::Skipped => $skippedCount++,

                        ValidationResultStatus::Failed => $failedCount++,
                    };

                    if (
                        $evaluation->status
                        === ValidationResultStatus::Failed
                    ) {
                        if (
                            $rule->severity
                            === CompatibilitySeverity::Error
                        ) {
                            $failedErrorCount++;
                        } else {
                            $warningCount++;
                        }
                    }
                }

                $runStatus = match (true) {
                    $failedErrorCount > 0 => ValidationRunStatus::Failed,

                    $warningCount > 0 => ValidationRunStatus::PassedWithWarnings,

                    default => ValidationRunStatus::Passed,
                };

                $configurationStatus =
                    $failedErrorCount > 0
                        ? ConfigurationStatus::Invalid
                        : ConfigurationStatus::Valid;

                $completedAt = now();

                $run->forceFill([
                    'status' => $runStatus,
                    'passed_count' => $passedCount,
                    'failed_count' => $failedCount,
                    'warning_count' => $warningCount,
                    'skipped_count' => $skippedCount,
                    'completed_at' => $completedAt,
                ])->save();

                $lockedConfiguration->forceFill([
                    'status' => $configurationStatus,

                    'validated_at' => $completedAt,

                    'last_activity_at' => $completedAt,
                ])->save();

                return $run->load('results');
            },
            attempts: 3,
        );
    }
}
