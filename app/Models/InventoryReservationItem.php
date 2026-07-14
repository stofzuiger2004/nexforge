<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryReservationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_reservation_id',
        'inventory_item_id',
        'configuration_item_id',
        'quantity',
        'released_quantity',
        'consumed_quantity',
        'sku_snapshot',
        'name_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'released_quantity' => 'integer',
            'consumed_quantity' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity - $this->released_quantity - $this->consumed_quantity);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class, 'inventory_reservation_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function configurationItem(): BelongsTo
    {
        return $this->belongsTo(ConfigurationItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_reservation_item_id');
    }
}
