<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VariantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'manufacturer_part_number',
        'barcode',
        'status',
        'is_default',
        'track_inventory',
        'weight_grams',
        'width_mm',
        'height_mm',
        'depth_mm',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => VariantStatus::class,
            'is_default' => 'boolean',
            'track_inventory' => 'boolean',
            'weight_grams' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'depth_mm' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VariantStatus::Active->value);
    }

    public function displayName(): string
    {
        $productName = $this->product->name;

        if ($this->name === null || $this->name === '') {
            return $productName;
        }

        return sprintf('%s - %s', $productName, $this->name);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function specificationValues(): HasMany
    {
        return $this->hasMany(ProductVariantSpecificationValue::class);
    }

    public function specificationOptions(): BelongsToMany
    {
        return $this->belongsToMany(SpecificationOption::class, 'product_variant_specification_option')->withTimestamps();
    }

    public function prices(): HasMany
    {
        return $this->hasMany(VariantPrice::class);
    }

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class, 'system_components')->withPivot(['slot', 'quantity', 'is_required', 'is_replaceable', 'sort_order'])->withTimestamps();
    }

    public function configurationItems(): HasMany
    {
        return $this->hasMany(ConfigurationItem::class, 'product_variant_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(inventoryItem::class, 'product_variant_id');
    }
}
