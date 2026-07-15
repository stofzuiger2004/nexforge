<?php

declare(strict_types=1);

use App\Enums\ConfigurationStatus;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderStatus;
use App\Models\Configuration;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\System;
use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\CompatibilityRuleSeeder;
use Database\Seeders\DemoInventorySeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

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

function startReviewConfiguration(): Configuration
{
    $system = System::query()
        ->where(
            'slug',
            '1080p-starter',
        )
        ->firstOrFail();

    test()->post(
        route(
            'configurator.start',
            $system,
        ),
    )->assertRedirect();

    return Configuration::query()
        ->latest('id')
        ->firstOrFail();
}

test(
    'a valid configuration can enter review and reserve stock',
    function (): void {
        $configuration =
            startReviewConfiguration();

        $this->post(
            route(
                'configurator.review.start',
                $configuration,
            ),
        )->assertRedirect(
            route(
                'configurator.review.show',
                $configuration,
            ),
        );

        $configuration->refresh();

        expect($configuration->status)
            ->toBe(
                ConfigurationStatus::ReadyForCheckout,
            );

        $reservation =
            InventoryReservation::query()
                ->where(
                    'configuration_id',
                    $configuration->id,
                )
                ->firstOrFail();

        expect($reservation->status)
            ->toBe(
                InventoryReservationStatus::Active,
            )

            ->and(
                $reservation
                    ->configuration_version,
            )
            ->toBe(
                $configuration->version,
            );

        expect(
            InventoryItem::query()
                ->sum('quantity_reserved'),
        )->toBeGreaterThan(0);
    },
);

test(
    'the review page shows the reserved configuration',
    function (): void {
        $configuration =
            startReviewConfiguration();

        $this->post(
            route(
                'configurator.review.start',
                $configuration,
            ),
        );

        $this->get(
            route(
                'configurator.review.show',
                $configuration,
            ),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'storefront/configurator/review',
                    )

                    ->where(
                        'review.configuration.public_id',
                        $configuration
                            ->public_id,
                    )

                    ->where(
                        'review.pricing.configuration_total_in_cents',
                        99900,
                    )

                    ->where(
                        'review.validation.is_current',
                        true,
                    )

                    ->where(
                        'review.can_submit',
                        true,
                    )

                    ->has(
                        'review.components',
                        8,
                    ),
            );
    },
);

test(
    'returning to the configurator releases reserved stock',
    function (): void {
        $configuration =
            startReviewConfiguration();

        $this->post(
            route(
                'configurator.review.start',
                $configuration,
            ),
        );

        $reservation =
            InventoryReservation::query()
                ->where(
                    'configuration_id',
                    $configuration->id,
                )
                ->firstOrFail();

        $this->delete(
            route(
                'configurator.review.destroy',
                $configuration,
            ),
        )->assertRedirect(
            route(
                'configurator.show',
                $configuration,
            ),
        );

        expect(
            $configuration
                ->fresh()
                ->status,
        )->toBe(
            ConfigurationStatus::Valid,
        );

        expect(
            $reservation
                ->fresh()
                ->status,
        )->toBe(
            InventoryReservationStatus::Cancelled,
        );

        expect(
            InventoryItem::query()
                ->sum('quantity_reserved'),
        )->toBe(0);
    },
);

test(
    'submitting review creates the immutable order',
    function (): void {
        $configuration =
            startReviewConfiguration();

        $this->post(
            route(
                'configurator.review.start',
                $configuration,
            ),
        );

        $response = $this->post(
            route(
                'checkout.orders.store',
                $configuration,
            ),
            [
                'email' => 'customer@example.com',

                'phone' => '+32 470 00 00 00',

                'shipping' => [
                    'first_name' => 'Test',
                    'last_name' => 'Customer',
                    'company' => '',
                    'vat_number' => '',
                    'address_line_1' => 'Teststraat 1',
                    'address_line_2' => '',
                    'postal_code' => '8400',
                    'city' => 'Oostende',
                    'state' => '',
                    'country_code' => 'BE',
                ],

                'billing_same_as_shipping' => true,

                'billing' => [
                    'first_name' => '',
                    'last_name' => '',
                    'company' => '',
                    'vat_number' => '',
                    'address_line_1' => '',
                    'address_line_2' => '',
                    'postal_code' => '',
                    'city' => '',
                    'state' => '',
                    'country_code' => 'BE',
                ],

                'terms' => true,
            ],
        );

        $order = Order::query()
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'checkout.payment.show',
                $order,
            ),
        );

        expect($order->status)
            ->toBe(
                OrderStatus::PendingPayment,
            )

            ->and(
                $order
                    ->total_in_cents,
            )
            ->toBe(99900)

            ->and(
                $order
                    ->items()
                    ->count(),
            )
            ->toBe(1)

            ->and(
                $order
                    ->items()
                    ->firstOrFail()
                    ->components()
                    ->count(),
            )
            ->toBe(8);

        expect(
            $configuration
                ->fresh()
                ->status,
        )->toBe(
            ConfigurationStatus::Converted,
        );

        $reservation =
            InventoryReservation::query()
                ->where(
                    'configuration_id',
                    $configuration->id,
                )
                ->firstOrFail();

        expect(
            $reservation->order_id,
        )->toBe($order->id);

        $response->assertSessionHas(
            sprintf(
                'orders.access.%s',
                $order->public_id,
            ),
        );
    },
);

test(
    'an expired review reservation unlocks the configuration',
    function (): void {
        $configuration =
            startReviewConfiguration();

        $this->post(
            route(
                'configurator.review.start',
                $configuration,
            ),
        );

        $reservation =
            InventoryReservation::query()
                ->where(
                    'configuration_id',
                    $configuration->id,
                )
                ->firstOrFail();

        $reservation->forceFill([
            'expires_at' => now()->subSecond(),
        ])->save();

        $this
            ->artisan(
                'inventory:expire-reservations',
            )
            ->assertSuccessful();

        expect(
            $reservation
                ->fresh()
                ->status,
        )->toBe(
            InventoryReservationStatus::Expired,
        );

        expect(
            $configuration
                ->fresh()
                ->status,
        )->toBe(
            ConfigurationStatus::Valid,
        );
    },
);
