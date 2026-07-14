<?php

declare(strict_types=1);

use App\Enums\ComponentSlot;
use App\Enums\InventoryReservationStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Configuration;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\PriceList;
use App\Models\ProductVariant;
use App\Models\System;
use App\Models\Warehouse;
use App\Services\Configurations\ConfigurationCreator;
use App\Services\Configurations\ConfigurationEditor;
use App\Services\Configurations\ConfigurationValidator;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Inventory\InventoryStockService;
use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\CompatibilityRuleSeeder;
use Database\Seeders\DemoInventorySeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Database\Seeders\WarehouseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

function createValidatedInventoryConfiguration():
    Configuration {
    $system = System::query()
        ->where('slug', '1080p-starter')
        ->firstOrFail();

    $priceList = PriceList::query()
        ->where('code', 'retail-eur')
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

    return $configuration->fresh();
}

test(
    'receiving stock is idempotent',
    function (): void {
        $warehouse = Warehouse::query()
            ->where('code', 'main')
            ->firstOrFail();

        $variant = ProductVariant::query()
            ->where('sku', 'GPU-RTX-4060')
            ->firstOrFail();

        $inventoryItem = InventoryItem::query()
            ->where(
                'warehouse_id',
                $warehouse->id,
            )
            ->where(
                'product_variant_id',
                $variant->id,
            )
            ->firstOrFail();

        $before =
            $inventoryItem->quantity_on_hand;

        $service = app(
            InventoryStockService::class,
        );

        $service->receive(
            warehouse: $warehouse,
            variant: $variant,
            quantity: 5,
            idempotencyKey: 'test-receipt-1',
        );

        /*
         * A retry with the same key must not add five more.
         */
        $service->receive(
            warehouse: $warehouse,
            variant: $variant,
            quantity: 5,
            idempotencyKey: 'test-receipt-1',
        );

        expect(
            $inventoryItem
                ->fresh()
                ->quantity_on_hand,
        )->toBe($before + 5);

        $this->assertDatabaseCount(
            'inventory_movements',
            ProductVariant::query()
                ->where('track_inventory', true)
                ->count()
                + 1,
        );
    },
);

test(
    'a validated configuration can reserve stock',
    function (): void {
        $configuration =
            createValidatedInventoryConfiguration();

        $reservation = app(
            InventoryReservationService::class,
        )->reserveConfiguration(
            configuration: $configuration,
            idempotencyKey:
                'test-reservation-1',
        );

        expect($reservation->status)
            ->toBe(
                InventoryReservationStatus::Active,
            )

            ->and($reservation->configuration_version)
            ->toBe($configuration->version)

            ->and($reservation->items)
            ->toHaveCount(8);

        foreach ($reservation->items as $item) {
            expect(
                $item
                    ->inventoryItem
                    ->fresh()
                    ->quantity_reserved,
            )->toBeGreaterThanOrEqual(
                $item->quantity,
            );
        }
    },
);

test(
    'a reservation is not partially created when stock is insufficient',
    function (): void {
        $configuration =
            createValidatedInventoryConfiguration();

        $gpuVariant = ProductVariant::query()
            ->where('sku', 'GPU-RTX-4060')
            ->firstOrFail();

        $gpuInventory = InventoryItem::query()
            ->where(
                'product_variant_id',
                $gpuVariant->id,
            )
            ->firstOrFail();

        app(
            InventoryStockService::class,
        )->setPhysicalCount(
            inventoryItem: $gpuInventory,
            countedQuantity: 0,
            reason: 'Test shortage.',
        );

        expect(
            fn () => app(
                InventoryReservationService::class,
            )->reserveConfiguration(
                configuration:
                    $configuration,
            ),
        )->toThrow(
            InsufficientInventoryException::class,
        );

        expect(
            InventoryReservation::query()->count(),
        )->toBe(0);

        expect(
            InventoryItem::query()
                ->sum('quantity_reserved'),
        )->toBe(0);
    },
);

test(
    'releasing a reservation returns stock to availability',
    function (): void {
        $configuration =
            createValidatedInventoryConfiguration();

        $service = app(
            InventoryReservationService::class,
        );

        $reservation =
            $service->reserveConfiguration(
                configuration:
                    $configuration,
            );

        $service->release(
            reservation: $reservation,
            reason: 'Customer abandoned configuration.',
        );

        expect(
            $reservation->fresh()->status,
        )->toBe(
            InventoryReservationStatus::Released,
        );

        foreach (
            $reservation->items
            as $reservationItem
        ) {
            expect(
                $reservationItem
                    ->inventoryItem
                    ->fresh()
                    ->quantity_reserved,
            )->toBe(0);
        }
    },
);

test(
    'consuming a reservation reduces physical stock',
    function (): void {
        $configuration =
            createValidatedInventoryConfiguration();

        $service = app(
            InventoryReservationService::class,
        );

        $reservation =
            $service->reserveConfiguration(
                configuration:
                    $configuration,
            );

        $before = $reservation
            ->items
            ->mapWithKeys(
                static fn ($item): array => [
                    $item->inventory_item_id =>
                        $item
                            ->inventoryItem
                            ->quantity_on_hand,
                ],
            );

        $service->consume(
            reservation: $reservation,
            reason: 'Configuration sent to assembly.',
        );

        expect(
            $reservation->fresh()->status,
        )->toBe(
            InventoryReservationStatus::Consumed,
        );

        foreach (
            $reservation->items
            as $reservationItem
        ) {
            $inventoryItem =
                $reservationItem
                    ->inventoryItem
                    ->fresh();

            expect(
                $inventoryItem
                    ->quantity_reserved,
            )->toBe(0);

            expect(
                $inventoryItem
                    ->quantity_on_hand,
            )->toBe(
                $before[
                    $inventoryItem->id
                ] - $reservationItem->quantity,
            );
        }
    },
);

test(
    'expired reservations are released by the command',
    function (): void {
        $configuration =
            createValidatedInventoryConfiguration();

        $reservation = app(
            InventoryReservationService::class,
        )->reserveConfiguration(
            configuration:
                $configuration,
        );

        /*
         * Direct update is acceptable here because this test is
         * specifically simulating passage of time.
         */
        $reservation->forceFill([
            'expires_at' =>
                now()->subSecond(),
        ])->save();

        $this
            ->artisan(
                'inventory:expire-reservations',
            )
            ->assertSuccessful();

        expect(
            $reservation->fresh()->status,
        )->toBe(
            InventoryReservationStatus::Expired,
        );

        expect(
            InventoryItem::query()
                ->sum('quantity_reserved'),
        )->toBe(0);
    },
);

test(
    'editing a configuration cancels its active reservation',
    function (): void {
        $configuration =
            createValidatedInventoryConfiguration();

        $reservation = app(
            InventoryReservationService::class,
        )->reserveConfiguration(
            configuration:
                $configuration,
        );

        $replacementGpu =
            ProductVariant::query()
                ->where(
                    'sku',
                    'GPU-RTX-4070-SUPER',
                )
                ->firstOrFail();

        app(
            ConfigurationEditor::class,
        )->setComponent(
            configuration:
                $configuration,

            slot:
                ComponentSlot::GraphicsCard,

            variant:
                $replacementGpu,
        );

        expect(
            $reservation->fresh()->status,
        )->toBe(
            InventoryReservationStatus::Cancelled,
        );

        expect(
            InventoryItem::query()
                ->sum('quantity_reserved'),
        )->toBe(0);
    },
);