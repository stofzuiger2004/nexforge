<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SystemStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class System extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'short_description',
        'description',
        'status',
        'is_featured',
        'is_configurable',
        'sort_order',
        'published_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => SystemStatus::class,
            'is_featured' => 'boolean',
            'is_configurable' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', SystemStatus::Active->value)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function components(): HasMany
    {
        return $this->hasMany(SystemComponent::class)->orderBy('sort_order')->orderBy('id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'system_components')->withPivot(['slot', 'quantity', 'is_required', 'is_replaceable', 'sort_order'])->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(SystemImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(SystemPrice::class);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(Configuration::class, 'source_system_id');
    }
}
