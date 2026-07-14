<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRefund extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'public_id',
        'payment_id',
        'requested_by_user_id',
        'provider',
        'provider_refund_id',
        'status',
        'amount_in_cents',
        'currency',
        'description',
        'reason',
        'idempotency_key',
        'provider_created_at',
        'refunded_at',
        'failed_at',
        'cancelled_at',
        'provider_snapshot',
        'metadata',
    ];

    protected $hidden = ['idempotency_key'];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentRefundStatus::class,
            'amount_in_cents' => 'integer',
            'provider_created_at' => 'datetime',
            'refunded_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'provider_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
