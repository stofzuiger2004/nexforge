<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\ValidationResultStatus;

final readonly class RuleEvaluation
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public ValidationResultStatus $status,
        public ?string $message = null,
        public array $context = [],
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public static function passed(
        array $context = [],
    ): self {
        return new self(
            ValidationResultStatus::Passed,
            null,
            $context,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function failed(
        string $message,
        array $context = [],
    ): self {
        return new self(
            ValidationResultStatus::Failed,
            $message,
            $context,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function skipped(
        ?string $message = null,
        array $context = [],
    ): self {
        return new self(
            ValidationResultStatus::Skipped,
            $message,
            $context,
        );
    }
}
