<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SystemPriceHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'system_price_id',
        'system_id',
        'price_list_id',
        'actor_user_id',
        'old_amount_in_cents',
        'new_amount_in_cents',
        'old_compare_at_amount_in_cents',
        'new_compare_at_amount_in_cents',
        'lock_version_before',
        'lock_version_after',
        'reason',
        'changed_at'
    ];

    protected function casts(): array{
        return [
            'old_amount_in_cents' => 'integer',
            'new_amount_in_cents' => 'integer',
            'old_compare_at_amount_in_cents' => 'integer',
            'new_compare_at_amount_in_cents' => 'integer',
            'lock_version_before' => 'integer',
            'lock_version_after' => 'integer',
            'changed_at' => 'datetime'
        ];
    }

    protected static function booted(): void
    {
        static::updating(
            static function (): never {
                throw new LogicException('System price histories are immutable.');
            }
        );
        static::deleting(
            static function (): never {
                throw new LogicException('System price histories cannot be deleted.');
            }
        );
    }

    public function systemPrice(): BelongsTo
    {
        return $this->belongsTo(SystemPrice::class);
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
