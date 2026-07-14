<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderAdjustmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAdjustment extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'line_number',
        'type',
        'code',
        'label',
        'amount_in_cents',
        'tax_in_cents',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'type' => OrderAdjustmentType::class,
            'amount_in_cents' => 'integer',
            'tax_in_cents' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
