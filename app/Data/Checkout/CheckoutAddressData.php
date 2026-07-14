<?php

declare(strict_types=1);

namespace App\Data\Checkout;

use App\Enums\OrderAddressType;

final readonly class CheckoutAddressData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $company,
        public ?string $vatNumber,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $postalCode,
        public string $city,
        public ?string $state,
        public string $countryCode,
        public string $email,
        public ?string $phone,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toOrderAttributes(
        OrderAddressType $type,
    ): array {
        return [
            'type' => $type,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'company' => $this->company,
            'vat_number' => $this->vatNumber,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
            'state' => $this->state,

            'country_code' => strtoupper($this->countryCode),

            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
