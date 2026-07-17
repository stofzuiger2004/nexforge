<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantPrice extends Model
{
    protected $fillable = [
        'price_list_id',
        'product_variant_id',
        'amount_in_cents',
        'compare_at_amount_in_cents',
        'lock_version'
    ];

    protected function casts(): array{
        return [
            'amount_in_cents' => 'integer',
            'compare_at_amount_in_cents' => 'integer',
            'lock_version' => 'integer'
        ];
    }

    public function priceList(): BelongsTo{
        return $this->belongsTo(PriceList::class);
    }

    public function variant(): BelongsTo{
        return $this->belongsTo(ProductVariant::class,'product_variant_id');
    }

    public function histories(): HasMany{
        return $this->hasMany(VariantPriceHistory::class)->latest('id');
    }
}