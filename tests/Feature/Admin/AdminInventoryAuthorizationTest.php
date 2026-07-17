<?php

declare(strict_types=1);

use App\Models\InventoryItem;
use App\Models\PriceList;
use App\Models\User;
use Database\Seeders\AdminAuthorizationSeeder;
use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\DemoInventorySeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    $this->inventoryItem = InventoryItem::query()
        ->whereHas(
            'variant',
            fn ($query) => $query->where(
                'sku',
                'GPU-RTX-4060',
            ),
        )
        ->firstOrFail();

    $this->priceList = PriceList::query()
        ->where('code', 'retail-eur')
        ->firstOrFail();
});

function createInventoryAdminUser(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->assignRole($role);

    return $user;
}

test('guests are redirected from inventory management', function (): void {
    $this->get(route('admin.inventory.index'))
        ->assertRedirect(route('login'));
});

test('support agents cannot view inventory management', function (): void {
    $supportAgent = createInventoryAdminUser(
        'support-agent',
    );

    $this->actingAs($supportAgent)
        ->get(route('admin.inventory.index'))
        ->assertForbidden();
});

test('order managers can view but cannot mutate inventory', function (): void {
    $orderManager = createInventoryAdminUser(
        'order-manager',
    );

    $this->actingAs($orderManager)
        ->get(route('admin.inventory.index'))
        ->assertOk();

    $this->actingAs($orderManager)
        ->post(
            route(
                'admin.inventory.adjustments.store',
                $this->inventoryItem,
            ),
            [
                'type' => 'receipt',
                'quantity' => 1,
                'reason' => 'Authorization test receipt.',
                'expected_lock_version' => $this
                    ->inventoryItem
                    ->lock_version,
                'idempotency_key' => (string) Str::ulid(),
            ],
        )
        ->assertForbidden();

    $this->actingAs($orderManager)
        ->patch(
            route(
                'admin.inventory.update',
                $this->inventoryItem,
            ),
            [
                'bin_location' => 'A-01',
                'safety_stock' => 2,
                'reorder_point' => 4,
                'is_active' => true,
                'expected_lock_version' => $this
                    ->inventoryItem
                    ->lock_version,
            ],
        )
        ->assertForbidden();

    $this->actingAs($orderManager)
        ->patch(
            route(
                'admin.variant-prices.update',
                [
                    'variant' => $this
                        ->inventoryItem
                        ->variant,
                    'priceList' => $this->priceList,
                ],
            ),
            [
                'amount' => '499.00',
                'compare_at_amount' => null,
                'reason' => 'Authorization test price.',
                'expected_lock_version' => 1,
            ],
        )
        ->assertForbidden();
});

test('order managers receive read only inventory permissions', function (): void {
    $orderManager = createInventoryAdminUser(
        'order-manager',
    );

    $this->actingAs($orderManager)
        ->get(
            route(
                'admin.inventory.show',
                $this->inventoryItem,
            ),
        )
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component(
                    'admin/inventory/show',
                )
                ->where(
                    'inventoryItem.can.adjust_inventory',
                    false,
                )
                ->where(
                    'inventoryItem.can.manage_inventory',
                    false,
                )
                ->where(
                    'inventoryItem.can.view_prices',
                    true,
                )
                ->where(
                    'inventoryItem.can.manage_prices',
                    false,
                ),
        );
});

test('administrators receive inventory mutation permissions', function (): void {
    $administrator = createInventoryAdminUser(
        'administrator',
    );

    $this->actingAs($administrator)
        ->get(
            route(
                'admin.inventory.show',
                $this->inventoryItem,
            ),
        )
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->where(
                    'inventoryItem.can.adjust_inventory',
                    true,
                )
                ->where(
                    'inventoryItem.can.manage_inventory',
                    true,
                )
                ->where(
                    'inventoryItem.can.view_prices',
                    true,
                )
                ->where(
                    'inventoryItem.can.manage_prices',
                    true,
                ),
        );
});