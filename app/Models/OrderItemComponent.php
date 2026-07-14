<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComponentSlot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemComponent extends Model
{
    protected $fillable = [
        'order_item_id',
        'source_configuration_item_id',
        'product_variant_id',
        'line_number',
        'slot',
        'sku_snapshot',
        'name_snapshot',
        'quantity',
        'unit_price_in_cents',
        'line_total_in_cents',
        'specifications_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'slot' => ComponentSlot::class,
            'quantity' => 'integer',
            'unit_price_in_cents' => 'integer',
            'line_total_in_cents' => 'integer',
            'specifications_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function sourceConfigurationItem(): BelongsTo
    {
        return $this->belongsTo(ConfigurationItem::class, 'source_configuration_item_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
