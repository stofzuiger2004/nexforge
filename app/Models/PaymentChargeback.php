<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentChargebackStatus;
use App\Enums\PaymentProvider;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentChargeback extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'public_id',
        'payment_id',
        'provider',
        'provider_chargeback_id',
        'status',
        'amount_in_cents',
        'currency',
        'reason_code',
        'reason_description',
        'provider_created_at',
        'reversed_at',
        'provider_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentChargebackStatus::class,
            'amount_in_cents' => 'integer',
            'provider_created_at' => 'datetime',
            'reversed_at' => 'datetime',
            'provider_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
