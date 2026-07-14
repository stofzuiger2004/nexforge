<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMode;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'public_id',
        'order_id',
        'provider',
        'provider_payment_id',
        'provider_profile_id',
        'attempt_number',
        'status',
        'provider_status',
        'mode',
        'method',
        'capture_mode',
        'sequence_type',
        'amount_in_cents',
        'currency',
        'description',
        'checkout_url',
        'idempotency_key',
        'amount_refunded_in_cents',
        'amount_charged_back_in_cents',
        'provider_created_at',
        'authorized_at',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'expired_at',
        'last_synced_at',
        'failure_code',
        'failure_message',
        'metadata',
        'provider_snapshot',
    ];

    protected $hidden = ['idempotency_key'];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentStatus::class,
            'mode' => PaymentMode::class,
            'attempt_number' => 'integer',
            'amount_in_cents' => 'integer',
            'amount_refunded_in_cents' => 'integer',
            'amount_charged_back_in_cents' => 'integer',
            'provider_created_at' => 'datetime',
            'authorized_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
            'provider_snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PaymentWebhookEvent::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function chargebacks(): HasMany
    {
        return $this->hasMany(PaymentChargeback::class);
    }
}
