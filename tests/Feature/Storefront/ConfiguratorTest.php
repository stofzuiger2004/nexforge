<?php

declare(strict_types=1);

use App\Enums\ConfigurationStatus;
use App\Models\Configuration;
use App\Models\PriceList;
use App\Models\ProductVariant;
use App\Models\System;
use App\Services\Configurations\ConfigurationCreator;
use App\Services\Configurations\ConfigurationValidator;
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

/**
 * Starts a configurator through the real HTTP endpoint so the
 * guest access token is also stored in the test session.
 */
function startGuestConfigurator(): Configuration
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
    'a guest can start a configuration from a published system',
    function (): void {
        $system = System::query()
            ->where(
                'slug',
                '1080p-starter',
            )
            ->firstOrFail();

        $response = $this->post(
            route(
                'configurator.start',
                $system,
            ),
        );

        $configuration =
            Configuration::query()
                ->latest('id')
                ->firstOrFail();

        $response->assertRedirect(
            route(
                'configurator.show',
                $configuration,
            ),
        );

        $response->assertSessionHas(
            sprintf(
                'configurator.access.%s',
                $configuration->public_id,
            ),
        );

        expect($configuration->status)
            ->toBe(
                ConfigurationStatus::Valid,
            )

            ->and(
                $configuration
                    ->total_in_cents,
            )
            ->toBe(99900)

            ->and(
                $configuration
                    ->items()
                    ->count(),
            )
            ->toBe(8);
    },
);

test(
    'the configurator displays groups and immediate option price differences',
    function (): void {
        $configuration =
            startGuestConfigurator();

        $this->get(
            route(
                'configurator.show',
                $configuration,
            ),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'storefront/configurator/show',
                    )

                    ->where(
                        'configuration.public_id',
                        $configuration
                            ->public_id,
                    )

                    ->where(
                        'configuration.pricing.base_price_in_cents',
                        99900,
                    )

                    ->where(
                        'configuration.pricing.total_in_cents',
                        99900,
                    )

                    ->where(
                        'configuration.validation.status',
                        'valid',
                    )

                    ->has(
                        'groups',
                        8,
                    )

                    ->where(
                        'groups.0.slot',
                        'cpu',
                    )

                    ->where(
                        'groups.0.selected.sku',
                        'CPU-AMD-R5-7600',
                    )

                    ->where(
                        'groups.0.options.1.sku',
                        'CPU-AMD-R7-7800X3D',
                    )

                    ->where(
                        'groups.0.options.1.price.delta_from_current_in_cents',
                        20000,
                    ),
            );
    },
);

test(
    'selecting a component updates the price and validates the configuration',
    function (): void {
        $configuration =
            startGuestConfigurator();

        $replacementGpu =
            ProductVariant::query()
                ->where(
                    'sku',
                    'GPU-RTX-4070-SUPER',
                )
                ->firstOrFail();

        $this->patch(
            route(
                'configurator.components.update',
                [
                    'configuration' => $configuration,

                    'slot' => 'graphics_card',
                ],
            ),
            [
                'variant_id' => $replacementGpu->id,

                'quantity' => 1,
            ],
        )->assertRedirect(
            route(
                'configurator.show',
                $configuration,
            ),
        );

        $configuration->refresh();

        expect(
            $configuration->version,
        )
            ->toBe(2)

            ->and(
                $configuration->status,
            )
            ->toBe(
                ConfigurationStatus::Valid,
            )

            ->and(
                $configuration
                    ->total_in_cents,
            )
            ->toBe(131900);

        $this->assertDatabaseHas(
            'configuration_items',
            [
                'configuration_id' => $configuration->id,

                'slot' => 'graphics_card',

                'product_variant_id' => $replacementGpu->id,

                'unit_price_in_cents' => 64900,
            ],
        );
    },
);

test(
    'a guest configuration cannot be opened without its session token',
    function (): void {
        $system = System::query()
            ->where(
                'slug',
                '1080p-starter',
            )
            ->firstOrFail();

        $priceList = PriceList::query()
            ->where(
                'code',
                'retail-eur',
            )
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

        $this->get(
            route(
                'configurator.show',
                $configuration,
            ),
        )->assertForbidden();
    },
);

test(
    'a component from the wrong category cannot fill a slot',
    function (): void {
        $configuration =
            startGuestConfigurator();

        $memoryVariant =
            ProductVariant::query()
                ->where(
                    'sku',
                    'RAM-CORSAIR-32-DDR5',
                )
                ->firstOrFail();

        $this->patch(
            route(
                'configurator.components.update',
                [
                    'configuration' => $configuration,

                    'slot' => 'cpu',
                ],
            ),
            [
                'variant_id' => $memoryVariant->id,
            ],
        )->assertSessionHasErrors(
            'variant_id',
        );

        $configuration->refresh();

        expect(
            $configuration->version,
        )->toBe(1);
    },
);
