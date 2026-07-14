<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'source_configuration_id',
        'source_configuration_version',
        'product_variant_id',
        'line_number',
        'type',
        'sku_snapshot',
        'name_snapshot',
        'description_snapshot',
        'quantity',
        'unit_price_in_cents',
        'subtotal_in_cents',
        'adjustment_total_in_cents',
        'tax_in_cents',
        'line_total_in_cents',
        'configuration_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderItemType::class,
            'line_number' => 'integer',
            'source_configuration_version' => 'integer',
            'quantity' => 'integer',
            'unit_price_in_cents' => 'integer',
            'subtotal_in_cents' => 'integer',
            'adjustment_total_in_cents' => 'integer',
            'tax_in_cents' => 'integer',
            'line_total_in_cents' => 'integer',
            'configuration_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sourceConfiguration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class, 'source_configuration_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(OrderItemComponent::class)->orderBy('line_number');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(OrderAdjustment::class);
    }
}
