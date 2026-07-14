<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatusCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'actor_user_id',
        'category',
        'from_status',
        'to_status',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'category' => OrderStatusCategory::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(
            static function (): never {
                throw new LogicException(
                    'Order status history is immutable.',
                );
            }
        );

        static::deleting(
            static function (): never {
                throw new LogicException(
                    'Order status history may not be deleted.',
                );
            }
        );
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
