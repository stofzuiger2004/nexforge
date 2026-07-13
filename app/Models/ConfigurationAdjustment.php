<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConfigurationAdjustmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'configuration_id',
        'type',
        'code',
        'label',
        'amount_in_cents',
        'is_taxable',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConfigurationAdjustmentType::class,
            'amount_in_cents' => 'integer',
            'is_taxable' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }
}
