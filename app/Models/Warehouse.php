<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'priority',
        'is_active',
        'can_assemble_systems',
        'can_fulfill_orders',
        'timezone',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country_code',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            'can_assemble_systems' => 'boolean',
            'can_fulfill_orders' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function scopeOperational(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForAssembly(Builder $query): Builder
    {
        return $query->operational()->where('can_assemble_systems', true)->orderBy('priority')->orderBy('id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }
}
