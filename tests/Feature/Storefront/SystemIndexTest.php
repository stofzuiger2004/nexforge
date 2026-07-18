<?php

declare(strict_types=1);

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
    'the gaming pc catalogue displays configurable system presets',
    function (): void {
        $this->get(
            route('gaming-pcs.index'),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'storefront/systems/index',
                    )
                    ->where(
                        'page.context',
                        'catalogue',
                    )
                    ->where(
                        'page.eyebrow',
                        'Gaming PCs',
                    )
                    ->has(
                        'systems',
                        3,
                    )
                    ->where(
                        'systems.0.slug',
                        '1080p-starter',
                    )
                    ->where(
                        'systems.0.is_configurable',
                        true,
                    )
                    ->where(
                        'systems.0.price.amount_in_cents',
                        99900,
                    )
                    ->where(
                        'systems.0.price.currency',
                        'EUR',
                    )
                    ->where(
                        'systems.0.components.processor.name',
                        'AMD Ryzen 5 7600',
                    )
                    ->where(
                        'systems.0.components.graphics_card.name',
                        'GeForce RTX 4060',
                    )
                    ->where(
                        'systems.0.availability.status',
                        'available',
                    ),
            );
    },
);

test(
    'the configurator landing page displays the same presets with configurator copy',
    function (): void {
        $this->get(
            route('configurator.index'),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'storefront/systems/index',
                    )
                    ->where(
                        'page.context',
                        'configurator',
                    )
                    ->where(
                        'page.eyebrow',
                        'PC configurator',
                    )
                    ->has(
                        'systems',
                        3,
                    )
                    ->where(
                        'systems.0.slug',
                        '1080p-starter',
                    ),
            );
    },
);

test(
    'non configurable systems are excluded from the preset pages',
    function (): void {
        System::query()
            ->where(
                'slug',
                '1080p-starter',
            )
            ->update([
                'is_configurable' => false,
            ]);

        $this->get(
            route('gaming-pcs.index'),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'systems',
                        2,
                    )
                    ->where(
                        'systems.0.slug',
                        '1440p-pro',
                    ),
            );

        $this->get(
            route('configurator.index'),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'systems',
                        2,
                    ),
            );
    },
);

test(
    'unpublished systems are excluded from the preset pages',
    function (): void {
        System::query()
            ->where(
                'slug',
                '1080p-starter',
            )
            ->update([
                'published_at' => null,
            ]);

        $this->get(
            route('gaming-pcs.index'),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'systems',
                        2,
                    ),
            );
    },
);

test(
    'systems without an active storefront price are excluded',
    function (): void {
        $system = System::query()
            ->where(
                'slug',
                '1080p-starter',
            )
            ->firstOrFail();

        $system->prices()->delete();

        $this->get(
            route('gaming-pcs.index'),
        )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'systems',
                        2,
                    ),
            );
    },
);
test(
    'a storefront preset can start a new configuration',
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

        $configuration = \App\Models\Configuration::query()
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'configurator.show',
                $configuration,
            ),
        );

        expect(
            $configuration->source_system_id,
        )->toBe($system->id);

        expect(
            $configuration
                ->items()
                ->count(),
        )->toBeGreaterThan(0);
    },
);