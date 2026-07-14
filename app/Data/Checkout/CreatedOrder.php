<?php

declare(strict_types=1);

namespace App\Data\Checkout;

use App\Models\Order;

final readonly class CreatedOrder
{
    public function __construct(
        public Order $order,
        public ?string $guestToken,
    ) {}
}
