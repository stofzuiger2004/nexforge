<?php

declare(strict_types=1);

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;
use Database\Seeders\AdminAuthorizationSeeder;
use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\DemoInventorySeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

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

    $this->inventoryItem = InventoryItem::query()
        ->whereHas(
            'variant',
            fn ($query) => $query->where(
                'sku',
                'GPU-RTX-4060',
            ),
        )
        ->firstOrFail();
});

test('an administrator can receive stock exactly once', function (): void {
    $before = $this->inventoryItem->quantity_on_hand;

    $key = 'admin-receipt-'.Str::ulid();

    $payload = [
        'type' => 'receipt',
        'quantity' => 5,
        'reason' => 'Supplier delivery received.',
        'reference' => 'PO-1001',
        'expected_lock_version' => $this
            ->inventoryItem
            ->lock_version,
        'idempotency_key' => $key,
    ];

    $this->actingAs($this->administrator)
        ->post(
            route(
                'admin.inventory.adjustments.store',
                $this->inventoryItem,
            ),
            $payload,
        )
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    /*
     * Repeat the original request to verify idempotency.
     * The original lock version is intentionally retained.
     */
    $this->actingAs($this->administrator)
        ->post(
            route(
                'admin.inventory.adjustments.store',
                $this->inventoryItem,
            ),
            $payload,
        )
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(
        $this->inventoryItem
            ->fresh()
            ->quantity_on_hand,
    )->toBe($before + 5);

    expect(
        InventoryMovement::query()
            ->where('idempotency_key', $key)
            ->count(),
    )->toBe(1);

    $movement = InventoryMovement::query()
        ->where('idempotency_key', $key)
        ->firstOrFail();

    expect($movement->type)
        ->toBe(InventoryMovementType::Receipt)
        ->and($movement->actor_user_id)
        ->toBe($this->administrator->id)
        ->and($movement->metadata['reference'])
        ->toBe('PO-1001');
});

test('stale stock adjustments are rejected', function (): void {
    $submittedVersion = $this
        ->inventoryItem
        ->lock_version;

    $this->inventoryItem->increment(
        'lock_version',
    );

    $this->actingAs($this->administrator)
        ->from(
            route(
                'admin.inventory.show',
                $this->inventoryItem,
            ),
        )
        ->post(
            route(
                'admin.inventory.adjustments.store',
                $this->inventoryItem,
            ),
            [
                'type' => 'receipt',
                'quantity' => 1,
                'reason' => 'Stale version test.',
                'expected_lock_version' => $submittedVersion,
                'idempotency_key' => (string) Str::ulid(),
            ],
        )
        ->assertSessionHasErrors(
            'expected_lock_version',
        );
});

test('stock cannot be reduced below reserved quantity', function (): void {
    $item = $this->inventoryItem->fresh();

    $item->forceFill([
        'quantity_on_hand' => 10,
        'quantity_reserved' => 8,
        'lock_version' => $item
            ->lock_version
            + 1,
    ])->saveOrFail();

    $this->actingAs($this->administrator)
        ->post(
            route(
                'admin.inventory.adjustments.store',
                $item,
            ),
            [
                'type' => 'damaged',
                'quantity' => 3,
                'reason' => 'Three units damaged.',
                'expected_lock_version' => $item
                    ->lock_version,
                'idempotency_key' => (string) Str::ulid(),
            ],
        )
        ->assertSessionHasErrors(
            'quantity',
        );

    expect(
        $item->fresh()->quantity_on_hand,
    )->toBe(10);
});

test('a physical count is audited even when quantity is unchanged', function (): void {
    $item = $this->inventoryItem->fresh();

    $this->actingAs($this->administrator)
    ->post(
        route(
            'admin.inventory.adjustments.store',
            $item,
        ),
        [
            'type' => 'stock_count',
            'quantity' => $item->quantity_on_hand,
            'reason' => 'Monthly physical stock count.',
            'expected_lock_version' => $item->lock_version,
            'idempotency_key' => (string) Str::ulid(),
        ],
    )
    ->assertRedirect()
    ->assertSessionHasNoErrors();

    $movement = $item
    ->movements()
    ->where(
        'type',
        InventoryMovementType::StockCount->value,
    )
    ->latest('id')
    ->firstOrFail();

    expect($movement->type)
        ->toBe(
            InventoryMovementType::StockCount,
        )
        ->and($movement->on_hand_delta)
        ->toBe(0)
        ->and($item->fresh()->last_counted_at)
        ->not->toBeNull();
});

test('inventory settings update without changing stock balances', function (): void {
    $item = $this->inventoryItem->fresh();

    $onHand = $item->quantity_on_hand;
    $reserved = $item->quantity_reserved;

    $response = $this->actingAs($this->administrator)
    ->patch(
        route(
            'admin.inventory.update',
            $item,
        ),
        [
            'bin_location' => 'B-14',
            'safety_stock' => 4,
            'reorder_point' => 8,
            'is_active' => true,
            'expected_lock_version' => $item->lock_version,
        ],
    );

$response
    ->assertRedirect()
    ->assertSessionHasNoErrors();

    $item = $item->fresh();

    expect($item->bin_location)
        ->toBe('B-14')
        ->and($item->safety_stock)
        ->toBe(4)
        ->and($item->reorder_point)
        ->toBe(8)
        ->and($item->quantity_on_hand)
        ->toBe($onHand)
        ->and($item->quantity_reserved)
        ->toBe($reserved);
});