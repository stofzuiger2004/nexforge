<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConfigurationStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configuration extends Model
{
    use HasFactory;
    use HasPublicUlid;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'source_system_id',
        'price_list_id',
        'guest_token_hash',
        'name',
        'status',
        'currency',
        'version',
        'subtotal_in_cents',
        'adjustment_total_in_cents',
        'tax_in_cents',
        'total_in_cents',
        'priced_at',
        'validated_at',
        'expires_at',
        'last_activity_at',
        'metadata',
    ];

    protected $hidden = [
        'guest_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConfigurationStatus::class,
            'version' => 'integer',

            'subtotal_in_cents' => 'integer',
            'adjustment_total_in_cents' => 'integer',
            'tax_in_cents' => 'integer',
            'total_in_cents' => 'integer',

            'priced_at' => 'datetime',
            'validated_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_activity_at' => 'datetime',

            'metadata' => 'array',
        ];
    }

    public function scopeEditable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ConfigurationStatus::Draft->value,
            ConfigurationStatus::Valid->value,
            ConfigurationStatus::Invalid->value,
        ]);
    }

    public function scopeForUser(
        Builder $query,
        User $user,
    ): Builder {
        return $query->where(
            'user_id',
            $user->getKey(),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceSystem(): BelongsTo
    {
        return $this->belongsTo(
            System::class,
            'source_system_id',
        );
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function items(): HasMany
    {
        return $this
            ->hasMany(ConfigurationItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function adjustments(): HasMany
    {
        return $this
            ->hasMany(ConfigurationAdjustment::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function validationRuns(): HasMany
    {
        return $this
            ->hasMany(ConfigurationValidationRun::class)
            ->latest('id');
    }

    public function latestValidationRun(): HasOne
    {
        return $this
            ->hasOne(ConfigurationValidationRun::class)
            ->latestOfMany();
    }
}
