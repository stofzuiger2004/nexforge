<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderAddressType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'first_name',
        'last_name',
        'company',
        'vat_number',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country_code',
        'email',
        'phone',
    ];

    protected function casts(): array
    {
        return ['type' => OrderAddressType::class];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
