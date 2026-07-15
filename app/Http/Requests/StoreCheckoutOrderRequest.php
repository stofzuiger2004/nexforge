<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\Checkout\CheckoutAddressData;
use App\Data\Checkout\CheckoutData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $shipping = (array) $this->input('shipping', []);
        $billing = (array) $this->input('billing', []);

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'shipping' => [
                ...$shipping,
                'country_code' => strtoupper(trim((string) $shipping['country_code'] ?? '')),
            ],
            'billing' => [
                ...$billing,
                'country_code' => strtoupper(trim((string) $billing['country_code'] ?? '')),
            ],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $countries = array_keys(config('checkout.shipping.allowed_countries', []));
        $billingRequired = Rule::requiredIf(fn (): bool => ! $this->boolean('billing_same_as_shipping'));

        return [
            'email' => [
                'required',
                'email',
                'max:254',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:64',
            ],
            'shipping.first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'shipping.last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'shipping.company' => [
                'nullable',
                'string',
                'max:150',
            ],
            'shipping.vat_number' => [
                'nullable',
                'string',
                'max:64',
            ],
            'shipping.address_line_1' => [
                'required',
                'string',
                'max:255',
            ],
            'shipping.address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'shipping.postal_code' => [
                'string',
                'required',
                'max:32',
            ],
            'shipping.city' => [
                'required',
                'string',
                'max:150',
            ],
            'shipping.state' => [
                'nullable',
                'string',
                'max:150',
            ],
            'shipping.country_code' => [
                'required',
                'string',
                'size:2',
                Rule::in($countries),
            ],
            'billing_same_as_shipping' => [
                'required',
                'boolean',
            ],
            'billing.first_name' => [
                $billingRequired,
                'nullable',
                'string',
                'max:100',
            ],
            'billing.last_name' => [
                $billingRequired,
                'nullable',
                'string',
                'max:100',
            ],
            'billing.company' => [
                'nullable',
                'string',
                'max:150',
            ],
            'billing.vat_number' => [
                'nullable',
                'string',
                'max:64',
            ],
            'billing.address_line_1' => [
                $billingRequired,
                'nullable',
                'string',
                'max:255',
            ],
            'billing.address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'billing.postal_code' => [
                $billingRequired,
                'nullable',
                'string',
                'max:32',
            ],
            'billing.city' => [
                $billingRequired,
                'nullable',
                'string',
                'max:150',
            ],
            'billing.state' => [
                'nullable',
                'string',
                'max:150',
            ],
            'billing.country_code' => [
                $billingRequired,
                'nullable',
                'string',
                'size:2',
                Rule::in($countries),
            ],
            'terms' => [
                'accepted',
            ],
        ];
    }

    public function checkoutData(): CheckoutData
    {
        $data = $this->validated();

        $shipping = $data['shipping'];

        $billing = $data['billing_same_as_shipping'] ? $shipping : $data['billing'];

        $email = $data['email'];
        $phone = $data['phone'] ?? null;

        return new CheckoutData(
            email: $email,
            locale: (string) config('checkout.default_locale', 'nl-BE'),
            billingAddress: $this->addressData($billing, $email, $phone),
            shippingAddress: $this->addressData($shipping, $email, $phone),
            shippingInCents: (int) config('checkout.shipping.flat_rate_in_cents', 0),
            termsAccepted: true
        );
    }

    private function addressData(array $data, string $email, ?string $phone): CheckoutAddressData
    {
        return new CheckoutAddressData(
            firstName: (string) $data['first_name'],
            lastName: (string) $data['last_name'],
            company: $this->nullableString($data['company'] ?? null),
            vatNumber: $this->nullableString($data['vat_number'] ?? null),
            addressLine1: (string) $data['address_line_1'],
            addressLine2: $this->nullableString($data['address_line_2'] ?? null),
            postalCode: (string) $data['postal_code'],
            city: (string) $data['city'],
            state: $this->nullableString($data['state'] ?? null),
            countryCode: (string) $data['country_code'],
            email: $email,
            phone: $phone
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
