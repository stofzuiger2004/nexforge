<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryReservationStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryReservation extends Model
{
    use HasFactory;
    use HasPublicUlid;

    protected $fillable = [
        'configuration_id',
        'warehouse_id',
        'created_by_user_id',
        'configuration_version',
        'status',
        'idempotency_key',
        'reserved_at',
        'expires_at',
        'released_at',
        'expired_at',
        'cancelled_at',
        'consumed_at',
        'metadata',
        'order_id',
        'committed_at',
    ];

    protected $hidden = ['idempotency_key'];

    protected function casts(): array
    {
        return [
            'configuration_version' => 'integer',
            'status' => InventoryReservationStatus::class,
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'consumed_at' => 'datetime',
            'metadata' => 'array',
            'committed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', InventoryReservationStatus::Active->value);
    }

    public function scopeDueForExpiration(Builder $query): Builder
    {
        return $query->active()->where('expires_at', '<=', now());
    }

    public function scopeHoldingStock(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InventoryReservationStatus::Active->value,
            InventoryReservationStatus::Committed->value,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryReservationItem::class)->orderBy('id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_reservation_id')->latest('id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
