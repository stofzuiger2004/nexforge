<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpecificationDataType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specification extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'data_type',
        'unit',
        'description',
        'is_filterable',
        'is_compatibility_key',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'data_type' => SpecificationDataType::class,
            'is_filterable' => 'boolean',
            'is_comparable' => 'boolean',
            'is_compatibility_key' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_specification')->withPivot(['is_required', 'sort_order'])->withTimestamps();
    }

    public function options(): HasMany
    {
        return $this->hasMany(SpecificationOption::class)->orderBy('sort_order')->orderBy('label');
    }

    public function scalarValues(): HasMany
    {
        return $this->hasMany(ProductVariantSpecificationValue::class);
    }
}
