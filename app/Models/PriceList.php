<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'currency',
        'prices_include_tax',
        'is_default',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'prices_include_tax' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeAvailable(
        Builder $query,
        ?DateTimeInterface $at = null,
    ): Builder {
        $at ??= now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) use ($at): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $at);
            })
            ->where(function (Builder $query) use ($at): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $at);
            });
    }

    public function variantPrices(): HasMany
    {
        return $this->hasMany(VariantPrice::class);
    }

    public function systemPrices(): HasMany
    {
        return $this->hasMany(SystemPrice::class);
    }

    public function sourceCompatibilityRules(): HasMany
    {
        return $this->hasMany(CompatibilityRule::class, 'source_specification_id');
    }

    public function targetCompatibilityRules(): HasMany
    {
        return $this->hasMany(CompatibilityRule::class, 'target_specification_id');
    }
}
