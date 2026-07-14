<?php

declare(strict_types=1);

use App\Data\Checkout\CheckoutAddressData;
use App\Data\Checkout\CheckoutData;
use App\Enums\ConfigurationStatus;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Configuration;
use App\Models\PriceList;
use App\Models\System;
use App\Services\Configurations\ConfigurationCreator;
use App\Services\Configurations\ConfigurationValidator;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Orders\OrderCreator;
use App\Services\Payments\PaymentAttemptCreator;
use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\CompatibilityRuleSeeder;
use Database\Seeders\DemoInventorySeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        PriceListSeeder::class,
        CatalogReferenceSeeder::class,
        CompatibilityRuleSeeder::class,
        DemoStorefrontSeeder::class,
        WarehouseSeeder::class,
        DemoInventorySeeder::class,
    ]);
});

function createCheckoutConfiguration(): array
{
    $system = System::query()
        ->where('slug', '1080p-starter')
        ->firstOrFail();

    $priceList = PriceList::query()
        ->where('code', 'retail-eur')
        ->firstOrFail();

    $configuration = app(
        ConfigurationCreator::class,
    )->createFromSystem(
        $system,
        $priceList,
    )->configuration;

    app(
        ConfigurationValidator::class,
    )->validate($configuration);

    $configuration = $configuration->fresh();

    $reservation = app(
        InventoryReservationService::class,
    )->reserveConfiguration(
        configuration: $configuration,
    );

    return [
        $configuration,
        $reservation,
    ];
}

function testCheckoutData(): CheckoutData
{
    $address = new CheckoutAddressData(
        firstName: 'Test',
        lastName: 'Customer',
        company: null,
        vatNumber: null,
        addressLine1: 'Teststraat 1',
        addressLine2: null,
        postalCode: '8400',
        city: 'Oostende',
        state: null,
        countryCode: 'BE',
        email: 'customer@example.com',
        phone: null,
    );

    return new CheckoutData(
        email: 'customer@example.com',
        locale: 'nl_BE',
        billingAddress: $address,
        shippingAddress: $address,
        shippingInCents: 0,
    );
}

test(
    'a validated reserved configuration becomes an immutable order',
    function (): void {
        [
            $configuration,
            $reservation,
        ] = createCheckoutConfiguration();

        $created = app(
            OrderCreator::class,
        )->createFromConfiguration(
            configuration: $configuration,
            reservation: $reservation,
            checkout: testCheckoutData(),
        );

        $order = $created->order;

        expect($order->status)
            ->toBe(OrderStatus::PendingPayment)

            ->and($order->payment_status)
            ->toBe(OrderPaymentStatus::Unpaid)

            ->and($order->total_in_cents)
            ->toBe(99900)

            ->and($order->items)
            ->toHaveCount(1)

            ->and($order->items->first()->components)
            ->toHaveCount(8)

            ->and($order->addresses)
            ->toHaveCount(2);

        expect(
            $configuration->fresh()->status,
        )->toBe(
            ConfigurationStatus::Converted,
        );

        expect(
            $reservation->fresh()->status,
        )->toBe(
            InventoryReservationStatus::Active,
        );

        expect(
            $reservation->fresh()->order_id,
        )->toBe($order->id);
    },
);

test(
    'a local mollie payment attempt is created before calling mollie',
    function (): void {
        [
            $configuration,
            $reservation,
        ] = createCheckoutConfiguration();

        $order = app(
            OrderCreator::class,
        )->createFromConfiguration(
            configuration: $configuration,
            reservation: $reservation,
            checkout: testCheckoutData(),
        )->order;

        $payment = app(
            PaymentAttemptCreator::class,
        )->create($order);

        expect($payment->status)
            ->toBe(PaymentStatus::Creating)

            ->and($payment->attempt_number)
            ->toBe(1)

            ->and($payment->amount_in_cents)
            ->toBe($order->total_in_cents)

            ->and($payment->currency)
            ->toBe('EUR')

            ->and($payment->provider_payment_id)
            ->toBeNull();
    },
);

test(
    'repeating local payment creation returns the same active attempt',
    function (): void {
        [
            $configuration,
            $reservation,
        ] = createCheckoutConfiguration();

        $order = app(
            OrderCreator::class,
        )->createFromConfiguration(
            configuration: $configuration,
            reservation: $reservation,
            checkout: testCheckoutData(),
        )->order;

        $service = app(
            PaymentAttemptCreator::class,
        );

        $first = $service->create($order);
        $second = $service->create($order);

        expect($second->id)
            ->toBe($first->id);

        $this->assertDatabaseCount(
            'payments',
            1,
        );
    },
);