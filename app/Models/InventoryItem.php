<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_variant_id',
        'bin_location',
        'is_active',
        'quantity_on_hand',
        'quantity_reserved',
        'safety_stock',
        'reorder_point',
        'lock_version',
        'last_counted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'safety_stock' => 'integer',
            'reorder_point' => 'integer',
            'lock_version' => 'integer',
            'last_counted_at' => 'datetime',
        ];
    }

    public function availableQuantity(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    public function reservableQuantity(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved - $this->safety_stock);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereNotNull('reorder_point')->whereRaw('(quantity_on_hand - quantity_reserved) <= reorder_point');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(InventoryReservationItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest('id');
    }
}
