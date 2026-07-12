<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantSpecificationValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'specification_id',
        'value_text',
        'value_integer',
        'value_decimal',
        'value_boolean',
    ];

    protected function casts(): array
    {
        return [
            'value_integer' => 'integer',
            'value_decimal' => 'decimal:4',
            'value_boolean' => 'boolean',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'product_variant_id');
    }

    public function specification(): BelongsTo
    {
        return $this->belongsTo(Specification::class);
    }
}
