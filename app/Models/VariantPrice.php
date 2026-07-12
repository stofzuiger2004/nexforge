<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_list_id',
        'product_variant_id',
        'amount_in_cents',
        'compare_at_amount_in_cents',
    ];

    protected function casts(): array
    {
        return [
            'amount_in_cents' => 'integer',
            'compare_at_amount_in_cents' => 'integer',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }
}
