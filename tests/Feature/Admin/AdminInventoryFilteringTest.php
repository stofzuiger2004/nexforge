<?php

declare(strict_types=1);

use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\AdminAuthorizationSeeder;
use Database\Seeders\CatalogReferenceSeeder;
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
        DemoStorefrontSeeder::class,
        WarehouseSeeder::class,
        DemoInventorySeeder::class,
        AdminAuthorizationSeeder::class,
    ]);

    $this->administrator = User::factory()
        ->create([
            'email_verified_at' => now(),
        ]);

    $this->administrator->assignRole(
        'administrator',
    );
});

test('inventory can be searched by sku', function (): void {
    $this->actingAs($this->administrator)
        ->get(
            route('admin.inventory.index', [
                'search' => 'GPU-RTX-4060',
            ]),
        )
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component(
                    'admin/inventory/index',
                )
                ->where(
                    'filters.search',
                    'GPU-RTX-4060',
                )
                ->has(
                    'inventoryItems.data',
                    1,
                )
                ->where(
                    'inventoryItems.data.0.variant.sku',
                    'GPU-RTX-4060',
                ),
        );
});

test('inventory can be filtered by warehouse', function (): void {
    $item = InventoryItem::query()
        ->with('warehouse')
        ->firstOrFail();

    $this->actingAs($this->administrator)
        ->get(
            route('admin.inventory.index', [
                'warehouse_id' => $item
                    ->warehouse_id,
            ]),
        )
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->where(
                    'filters.warehouse_id',
                    $item->warehouse_id,
                )
                ->where(
                    'inventoryItems.data.0.warehouse.id',
                    $item->warehouse_id,
                ),
        );
});

test('low stock filtering uses available stock', function (): void {
    $item = InventoryItem::query()
        ->firstOrFail();

    $item->forceFill([
        'quantity_on_hand' => 10,
        'quantity_reserved' => 8,
        'reorder_point' => 3,
        'is_active' => true,
    ])->saveOrFail();

    $this->actingAs($this->administrator)
        ->get(
            route('admin.inventory.index', [
                'stock_state' => 'low_stock',
                'search' => $item
                    ->variant
                    ->sku,
            ]),
        )
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->has(
                    'inventoryItems.data',
                    1,
                )
                ->where(
                    'inventoryItems.data.0.stock.available',
                    2,
                )
                ->where(
                    'inventoryItems.data.0.stock.state',
                    'low_stock',
                ),
        );
});