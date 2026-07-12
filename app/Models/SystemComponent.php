<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComponentSlot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_id',
        'product_variant_id',
        'slot',
        'quantity',
        'is_required',
        'is_replaceable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'slot' => ComponentSlot::class,
            'quantity' => 'integer',
            'is_required' => 'boolean',
            'is_replaceable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id',
        );
    }
}
