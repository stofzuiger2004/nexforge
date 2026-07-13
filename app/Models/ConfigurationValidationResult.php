<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompatibilitySeverity;
use App\Enums\ValidationResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationValidationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'validation_run_id',
        'compatibility_rule_id',
        'rule_key',
        'rule_name',
        'rule_revision',
        'status',
        'severity',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'rule_revision' => 'integer',
            'status' => ValidationResultStatus::class,
            'severity' => CompatibilitySeverity::class,
            'context' => 'array',
        ];
    }

    public function validationRun(): BelongsTo
    {
        return $this->belongsTo(
            ConfigurationValidationRun::class,
            'validation_run_id',
        );
    }

    public function compatibilityRule(): BelongsTo
    {
        return $this->belongsTo(
            CompatibilityRule::class,
        );
    }
}
