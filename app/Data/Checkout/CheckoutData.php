<?php

declare(strict_types=1);

namespace App\Data\Checkout;

final readonly class CheckoutData
{
    public function __construct(
        public string $email,
        public string $locale,
        public CheckoutAddressData $billingAddress,
        public CheckoutAddressData $shippingAddress,
        public int $shippingInCents = 0,
    ) {}
}
