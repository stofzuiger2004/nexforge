<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComponentSlot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConfigurationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'configuration_id',
        'product_variant_id',
        'source_system_component_id',
        'slot',
        'quantity',
        'sort_order',
        'sku_snapshot',
        'name_snapshot',
        'unit_price_in_cents',
        'line_total_in_cents',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'slot' => ComponentSlot::class,
            'quantity' => 'integer',
            'sort_order' => 'integer',
            'unit_price_in_cents' => 'integer',
            'line_total_in_cents' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function sourceSystemComponent(): BelongsTo
    {
        return $this->belongsTo(SystemComponent::class, 'source_system_component_id');
    }

    public function inventoryReservationItems(): HasMany
    {
        return $this->hasMany(inventoryReservationItem::class);
    }

    public function orderItemComponents(): HasMany
    {
        return $this->hasMany(OrderItemComponent::class, 'source_configuration_item_id');
    }
}
