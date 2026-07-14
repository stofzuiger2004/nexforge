<?php

declare(strict_types=1);

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

test(
    'the homepage displays featured systems from the catalogue',
    function (): void {
        $response = $this->get(
            route('home'),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'storefront/index',
                    )

                    ->has(
                        'featuredSystems',
                        3,
                    )

                    ->where(
                        'featuredSystems.0.slug',
                        '1080p-starter',
                    )

                    ->where(
                        'featuredSystems.0.price.amount_in_cents',
                        99900,
                    )

                    ->where(
                        'featuredSystems.0.price.currency',
                        'EUR',
                    )

                    ->where(
                        'featuredSystems.0.components.processor.name',
                        'AMD Ryzen 5 7600',
                    )

                    ->where(
                        'featuredSystems.0.components.graphics_card.name',
                        'GeForce RTX 4060',
                    )

                    ->where(
                        'featuredSystems.0.availability.status',
                        'available',
                    ),
            );
    },
);
