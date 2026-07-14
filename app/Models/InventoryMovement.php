<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryMovementType;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class InventoryMovement extends Model
{
    use HasFactory;
    use HasPublicUlid;

    public const UPDATED_AT = null;

    protected $fillable = [
        'inventory_item_id',
        'inventory_reservation_id',
        'inventory_reservation_item_id',
        'actor_user_id',
        'type',
        'on_hand_delta',
        'reserved_delta',
        'on_hand_after',
        'reserved_after',
        'correlation_id',
        'idempotency_key',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected $hidden = ['idempotency_key'];

    protected function casts(): array
    {
        return [
            'type' => InventoryMovementType::class,
            'on_hand_delta' => 'integer',
            'reserved_delta' => 'integer',
            'on_hand_after' => 'integer',
            'reserved_after' => 'integer',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(
            static function (): never {
                throw new LogicException(
                    'Inventory movements are immutable.',
                );
            },
        );

        static::deleting(
            static function (): never {
                throw new LogicException(
                    'Inventory movements may not be deleted.',
                );
            },
        );
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class, 'inventory_reservation_id');
    }

    public function reservationItem(): BelongsTo
    {
        return $this->belongsTo(InventoryReservationItem::class, 'inventory_reservation_item_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
