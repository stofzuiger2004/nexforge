<?php

declare(strict_types=1);

$allowedMethods = array_values(array_filter(array_map('trim', explode(',', (string) env('MOLLIE_ALLOWED_METHODS', '')))));
$countryLabels = [
    'BE' => 'Belgium',
    'NL' => 'Netherlands',
    'LU' => 'Luxembourg',
    'DE' => 'Germany',
    'FR' => 'France',
];

$allowedCountryCodes = array_values(array_filter(array_map(static fn (string $code): string => strtoupper(trim($code)), explode(',', (string) env('CHECKOUT_ALLOWED_COUNTRIES', 'BE,NL,LU,DE,FR')))));

$allowedCountries = [];

foreach ($allowedCountryCodes as $countryCode) {
    if (isset($countryLabels[$countryCode])) {
        $allowedCountries[$countryCode] = $countryLabels[$countryCode];
    }
}

return [
    'order_prefix' => env('CHECKOUT_ORDER_PREFIX', 'NF'),
    'default_locale' => env('CHECKOUT_DEFAULT_LOCALE', 'nl-BE'),
    'default_country' => env('CHECKOUT_DEFAULT_COUNTRY', 'BE'),
    'terms_version' => env('CHECKOUT_TERMS_VERSION', '2026-01'),
    'provider' => 'mollie',
    'shipping' => [
        'flat_rate_in_cents' => max(0, (int) env('CHECKOUT_SHIPPING_CENTS', 0)),
        'allowed_countries' => $allowedCountries,
    ],
    'mollie' => [
        'webhook_url' => env('MOLLIE_WEBHOOK_URL'),
        'allowed_methods' => $allowedMethods,
    ],
];
