<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\PaymentWebhookStatus;
use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'public_id',
        'provider',
        'provider_event_id',
        'event_type',
        'resource_type',
        'provider_resource_id',
        'payment_id',
        'payload',
        'payload_hash',
        'signature_verified_at',
        'processing_status',
        'attempt_count',
        'received_at',
        'processing_started_at',
        'processed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'processing_status' => PaymentWebhookStatus::class,
            'payload' => 'array',
            'attempt_count' => 'integer',
            'signature_verified_at' => 'datetime',
            'received_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
