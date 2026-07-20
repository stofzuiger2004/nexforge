<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_id',
        'price_list_id',
        'amount_in_cents',
        'compare_at_amount_in_cents',
        'lock_version'
    ];

    protected function casts(): array
    {
        return [
            'amount_in_cents' => 'integer',
            'compare_at_amount_in_cents' => 'integer',
            'lock_version'=>'integer'
        ];
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }
    public function histories(): HasMany{
        return $this->hasMany(SystemPriceHistory::class)->latest('id');
    }
}
