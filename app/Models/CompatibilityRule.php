<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompatibilityRuleType;
use App\Enums\CompatibilitySeverity;
use App\Enums\ComponentSlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompatibilityRule extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'rule_type',
        'severity',
        'source_slot',
        'source_specification_id',
        'target_slot',
        'target_specification_id',
        'failure_message',
        'settings',
        'revision',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rule_type' => CompatibilityRuleType::class,
            'severity' => CompatibilitySeverity::class,
            'source_slot' => ComponentSlot::class,
            'target_slot' => ComponentSlot::class,
            'settings' => 'array',
            'revision' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function sourceSpecification(): BelongsTo
    {
        return $this->belongsTo(
            Specification::class,
            'source_specification_id',
        );
    }

    public function targetSpecification(): BelongsTo
    {
        return $this->belongsTo(
            Specification::class,
            'target_specification_id',
        );
    }

    public function validationResults(): HasMany
    {
        return $this->hasMany(
            ConfigurationValidationResult::class,
        );
    }
}
