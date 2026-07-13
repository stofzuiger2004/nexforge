<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ValidationRunStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConfigurationValidationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'configuration_id',
        'status',
        'configuration_version',
        'rules_count',
        'passed_count',
        'failed_count',
        'warning_count',
        'skipped_count',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => ValidationRunStatus::class,
            'configuration_version' => 'integer',
            'rules_count' => 'integer',
            'passed_count' => 'integer',
            'failed_count' => 'integer',
            'warning_count' => 'integer',
            'skipped_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }

    public function results(): HasMany
    {
        return $this
            ->hasMany(
                ConfigurationValidationResult::class,
                'validation_run_id',
            )
            ->orderBy('id');
    }
}
