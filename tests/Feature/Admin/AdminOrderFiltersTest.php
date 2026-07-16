<?php

declare(strict_types=1);

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\AdminAuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(
        AdminAuthorizationSeeder::class,
    );

    $this->administrator =
        User::factory()->create([
            'email_verified_at' => now(),
        ]);

    $this->administrator
        ->assignRole('administrator');
});

function createFilterTestOrder(
    string $number,
    string $email,
    OrderStatus $status,
    int $total,
): Order {
    return Order::query()->create([
        'order_number' => $number,

        'status' => $status,

        'payment_status' => OrderPaymentStatus::Unpaid,

        'fulfillment_status' => OrderFulfillmentStatus::Unfulfilled,

        'customer_email' => $email,
        'customer_locale' => 'en_BE',

        'currency' => 'EUR',

        'subtotal_in_cents' => $total,
        'adjustment_total_in_cents' => 0,
        'shipping_in_cents' => 0,
        'tax_in_cents' => 0,
        'total_in_cents' => $total,

        'paid_in_cents' => 0,
        'refunded_in_cents' => 0,
        'charged_back_in_cents' => 0,

        'placed_at' => now(),
    ]);
}

test(
    'orders can be searched by order number',
    function (): void {
        createFilterTestOrder(
            'NF-SEARCH-001',
            'first@example.com',
            OrderStatus::PendingPayment,
            100000,
        );

        createFilterTestOrder(
            'NF-OTHER-002',
            'second@example.com',
            OrderStatus::Confirmed,
            200000,
        );

        $this
            ->actingAs(
                $this->administrator,
            )
            ->get(
                '/admin/orders?search=SEARCH-001',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'orders.data',
                        1,
                    )
                    ->where(
                        'orders.data.0.order_number',
                        'NF-SEARCH-001',
                    ),
            );
    },
);

test(
    'orders can be filtered by status',
    function (): void {
        createFilterTestOrder(
            'NF-PENDING-001',
            'pending@example.com',
            OrderStatus::PendingPayment,
            100000,
        );

        createFilterTestOrder(
            'NF-CONFIRMED-001',
            'confirmed@example.com',
            OrderStatus::Confirmed,
            200000,
        );

        $this
            ->actingAs(
                $this->administrator,
            )
            ->get(
                '/admin/orders?status=confirmed',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'orders.data',
                        1,
                    )
                    ->where(
                        'orders.data.0.status',
                        'confirmed',
                    ),
            );
    },
);

test(
    'orders can be sorted by total',
    function (): void {
        createFilterTestOrder(
            'NF-LOW',
            'low@example.com',
            OrderStatus::Confirmed,
            100000,
        );

        createFilterTestOrder(
            'NF-HIGH',
            'high@example.com',
            OrderStatus::Confirmed,
            300000,
        );

        $this
            ->actingAs(
                $this->administrator,
            )
            ->get(
                '/admin/orders?sort=total_high',
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'orders.data.0.order_number',
                        'NF-HIGH',
                    ),
            );
    },
);
