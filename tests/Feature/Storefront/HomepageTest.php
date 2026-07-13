<?php

declare(strict_types=1);

use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the homepage displays featured systems from the database', function () {
    $this->seed([
        PriceListSeeder::class,
        CatalogReferenceSeeder::class,
        DemoStorefrontSeeder::class,
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/index')
            ->has('featuredSystems', 3)
            ->where(
                'featuredSystems.0.slug',
                '1080p-starter',
            )
            ->where(
                'featuredSystems.0.processor',
                'AMD Ryzen 5 7600',
            )
            ->where(
                'featuredSystems.0.graphics_card',
                'GeForce RTX 4060',
            )
            ->where(
                'featuredSystems.0.price_in_cents',
                99900,
            )
            ->where(
                'featuredSystems.0.currency',
                'EUR',
            )
        );
});
