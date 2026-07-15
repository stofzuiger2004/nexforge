<?php

declare(strict_types=1);

use App\Enums\SystemStatus;
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

test(
    'a published system detail page is displayed',
    function (): void {
        $response = $this->get(
            route(
                'gaming-pcs.show',
                [
                    'slug' => '1080p-starter',
                ],
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'storefront/systems/show',
                    )

                    ->where(
                        'system.slug',
                        '1080p-starter',
                    )

                    ->where(
                        'system.name',
                        '1080p Starter',
                    )

                    ->where(
                        'system.price.amount_in_cents',
                        99900,
                    )

                    ->where(
                        'system.price.currency',
                        'EUR',
                    )

                    ->where(
                        'system.availability.status',
                        'available',
                    )

                    ->has(
                        'system.components',
                        8,
                    )

                    ->where(
                        'system.components.0.slot',
                        'cpu',
                    )

                    ->where(
                        'system.components.0.name',
                        'AMD Ryzen 5 7600',
                    )

                    ->where(
                        'system.components.0.is_replaceable',
                        true,
                    ),
            );
    },
);

test(
    'an unpublished system is not publicly accessible',
    function (): void {
        $system = System::query()
            ->where(
                'slug',
                '1080p-starter',
            )
            ->firstOrFail();

        $system->forceFill([
            'status' => SystemStatus::Draft,
        ])->save();

        $this->get(
            route(
                'gaming-pcs.show',
                [
                    'slug' => $system->slug,
                ],
            ),
        )->assertNotFound();
    },
);

test(
    'an unknown system returns a not found response',
    function (): void {
        $this->get(
            route(
                'gaming-pcs.show',
                [
                    'slug' => 'unknown-gaming-pc',
                ],
            ),
        )->assertNotFound();
    },
);
