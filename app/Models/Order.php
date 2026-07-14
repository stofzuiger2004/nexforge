<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'public_id',
        'order_number',
        'user_id',
        'guest_token_hash',
        'status',
        'payment_status',
        'fulfillment_status',
        'customer_email',
        'customer_locale',
        'currency',
        'subtotal_in_cents',
        'adjustment_total_in_cents',
        'shipping_in_cents',
        'tax_in_cents',
        'total_in_cents',
        'paid_in_cents',
        'refunded_in_cents',
        'charged_back_in_cents',
        'lock_version',
        'placed_at',
        'paid_at',
        'cancelled_at',
        'completed_at',
        'metadata',
    ];

    protected $hidden = ['guest_token_hash'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => OrderPaymentStatus::class,
            'fulfillment_status' => OrderFulfillmentStatus::class,
            'subtotal_in_cents' => 'integer',
            'adjustment_total_in_cents' => 'integer',
            'shipping_in_cents' => 'integer',
            'tax_in_cents' => 'integer',
            'total_in_cents' => 'integer',
            'paid_in_cents' => 'integer',
            'refunded_in_cents' => 'integer',
            'charged_back_in_cents' => 'integer',
            'lock_version' => 'integer',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function scopePendingPayment(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PendingPayment->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('line_number');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(OrderAdjustment::class)->orderBy('line_number');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('attempt_number');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }
}
