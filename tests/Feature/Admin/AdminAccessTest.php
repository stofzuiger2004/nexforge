<?php

declare(strict_types=1);

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\AdminAuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(
        AdminAuthorizationSeeder::class,
    );
});

function createAdminTestOrder(
    array $attributes = [],
): Order {
    return Order::query()->create([
        'order_number' => 'NF-TEST-'.fake()->unique()->numerify(
            '######',
        ),

        'user_id' => $attributes['user_id']
            ?? null,

        'status' => $attributes['status']
            ?? OrderStatus::PendingPayment,

        'payment_status' => $attributes['payment_status']
            ?? OrderPaymentStatus::Unpaid,

        'fulfillment_status' => $attributes['fulfillment_status']
            ?? OrderFulfillmentStatus::Unfulfilled,

        'customer_email' => $attributes['customer_email']
            ?? fake()->safeEmail(),

        'customer_locale' => 'en_BE',
        'currency' => 'EUR',

        'subtotal_in_cents' => $attributes['total_in_cents']
            ?? 100000,

        'adjustment_total_in_cents' => 0,
        'shipping_in_cents' => 0,
        'tax_in_cents' => 0,

        'total_in_cents' => $attributes['total_in_cents']
            ?? 100000,

        'paid_in_cents' => 0,
        'refunded_in_cents' => 0,
        'charged_back_in_cents' => 0,

        'placed_at' => now(),
    ]);
}

test(
    'a guest is redirected to login',
    function (): void {
        $this->get('/admin')
            ->assertRedirect(
                route('login'),
            );
    },
);

test(
    'a regular customer cannot access admin pages',
    function (): void {
        $customer =
            User::factory()->create([
                'email_verified_at' => now(),
            ]);

        $this
            ->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();

        $this
            ->actingAs($customer)
            ->get('/admin/orders')
            ->assertForbidden();
    },
);

test(
    'an administrator can access the dashboard and orders',
    function (): void {
        $administrator =
            User::factory()->create([
                'email_verified_at' => now(),
            ]);

        $administrator->assignRole(
            'administrator',
        );

        createAdminTestOrder();

        $this
            ->actingAs($administrator)
            ->get('/admin')
            ->assertOk();

        $this
            ->actingAs($administrator)
            ->get('/admin/orders')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'admin/orders/index',
                    )
                    ->has(
                        'orders.data',
                        1,
                    ),
            );
    },
);

test(
    'payment data is hidden from support agents',
    function (): void {
        $supportAgent =
            User::factory()->create([
                'email_verified_at' => now(),
            ]);

        $supportAgent->assignRole(
            'support-agent',
        );

        $order =
            createAdminTestOrder();

        Payment::query()->create([
            'order_id' => $order->id,

            'provider' => PaymentProvider::Mollie,

            'provider_payment_id' => 'tr_test_support',

            'attempt_number' => 1,

            'status' => PaymentStatus::Open,

            'provider_status' => 'open',

            'amount_in_cents' => $order->total_in_cents,

            'currency' => $order->currency,

            'description' => 'Test payment',

            'idempotency_key' => 'test-support-payment',
        ]);

        $this
            ->actingAs($supportAgent)
            ->get(
                route(
                    'admin.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'order.can.view_payments',
                        false,
                    )
                    ->where(
                        'order.payments',
                        null,
                    ),
            );
    },
);

test(
    'payment data is visible to administrators',
    function (): void {
        $administrator =
            User::factory()->create([
                'email_verified_at' => now(),
            ]);

        $administrator->assignRole(
            'administrator',
        );

        $order =
            createAdminTestOrder();

        Payment::query()->create([
            'order_id' => $order->id,

            'provider' => PaymentProvider::Mollie,

            'provider_payment_id' => 'tr_test_admin',

            'attempt_number' => 1,

            'status' => PaymentStatus::Open,

            'provider_status' => 'open',

            'amount_in_cents' => $order->total_in_cents,

            'currency' => $order->currency,

            'description' => 'Test payment',

            'idempotency_key' => 'test-admin-payment',
        ]);

        $this
            ->actingAs($administrator)
            ->get(
                route(
                    'admin.orders.show',
                    $order,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'order.can.view_payments',
                        true,
                    )
                    ->has(
                        'order.payments',
                        1,
                    )
                    ->where(
                        'order.payments.0.provider_payment_id',
                        'tr_test_admin',
                    ),
            );
    },
);
