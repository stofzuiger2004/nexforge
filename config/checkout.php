<?php

declare(strict_types=1);

$allowedMethods = array_values(array_filter(array_map('trim', explode(',', (string) env('MOLLIE_ALLOWED_METHODS', '')))));

return [
    'order_prefix' => env('CHECKOUT_ORDER_PREFIX', 'NF'),
    'default_locale' => env('CHECKOUT_DEFAULT_LOCALE', 'nl-BE'),
    'provider' => 'mollie',
    'mollie' => [
        'webhook_url' => env('MOLLIE_WEBHOOK_URL'),
        'allowed_methods' => $allowedMethods,
    ],
];
