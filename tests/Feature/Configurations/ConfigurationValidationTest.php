<?php

declare(strict_types=1);

use App\Enums\ComponentSlot;
use App\Enums\ConfigurationStatus;
use App\Enums\ValidationResultStatus;
use App\Enums\ValidationRunStatus;
use App\Models\PriceList;
use App\Models\ProductVariant;
use App\Models\System;
use App\Services\Catalog\VariantSpecificationWriter;
use App\Services\Configurations\ConfigurationCreator;
use App\Services\Configurations\ConfigurationEditor;
use App\Services\Configurations\ConfigurationValidator;
use App\Services\Configurations\CreatedConfiguration;
use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\CompatibilityRuleSeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        PriceListSeeder::class,
        CatalogReferenceSeeder::class,
        CompatibilityRuleSeeder::class,
        DemoStorefrontSeeder::class,
    ]);
});

function createPhaseTwoDemoConfiguration(): CreatedConfiguration
{
    $system = System::query()
        ->where('slug', '1080p-starter')
        ->firstOrFail();

    $priceList = PriceList::query()
        ->where('code', 'retail-eur')
        ->firstOrFail();

    return app(
        ConfigurationCreator::class,
    )->createFromSystem(
        $system,
        $priceList,
    );
}

test(
    'a prebuilt system can be copied into a guest configuration',
    function (): void {
        $created =
            createPhaseTwoDemoConfiguration();

        $configuration =
            $created->configuration;

        expect($created->guestToken)
            ->not
            ->toBeNull()

            ->and($configuration->public_id)
            ->not
            ->toBeEmpty()

            ->and($configuration->status)
            ->toBe(ConfigurationStatus::Draft)

            ->and($configuration->items)
            ->toHaveCount(8)

            ->and(
                $configuration
                    ->total_in_cents,
            )
            ->toBe(99900);

        $this->assertDatabaseHas(
            'configurations',
            [
                'id' => $configuration->id,

                'source_system_id' => $configuration
                    ->source_system_id,

                'total_in_cents' => 99900,
            ],
        );
    },
);

test(
    'a compatible configuration passes validation',
    function (): void {
        $configuration =
            createPhaseTwoDemoConfiguration()
                ->configuration;

        $run = app(
            ConfigurationValidator::class,
        )->validate($configuration);

        expect($run->status)
            ->toBe(ValidationRunStatus::Passed)

            ->and($run->failed_count)
            ->toBe(0)

            ->and($run->results)
            ->not
            ->toBeEmpty();

        expect(
            $configuration->fresh()->status,
        )->toBe(ConfigurationStatus::Valid);
    },
);

test(
    'editing a component increments the version and invalidates validation',
    function (): void {
        $configuration =
            createPhaseTwoDemoConfiguration()
                ->configuration;

        app(
            ConfigurationValidator::class,
        )->validate($configuration);

        $replacement =
            ProductVariant::query()
                ->where(
                    'sku',
                    'GPU-RTX-4070-SUPER',
                )
                ->firstOrFail();

        $updated = app(
            ConfigurationEditor::class,
        )->setComponent(
            $configuration,
            ComponentSlot::GraphicsCard,
            $replacement,
        );

        expect($updated->version)
            ->toBe(2)

            ->and($updated->status)
            ->toBe(ConfigurationStatus::Draft)

            ->and($updated->validated_at)
            ->toBeNull()

            ->and($updated->total_in_cents)
            ->toBe(131900);
    },
);

test(
    'an incompatible cpu and motherboard socket fails validation',
    function (): void {
        $configuration =
            createPhaseTwoDemoConfiguration()
                ->configuration;

        $motherboardItem =
            $configuration
                ->items
                ->first(
                    static fn ($item): bool => $item->slot
                        === ComponentSlot::Motherboard,
                );

        expect($motherboardItem)
            ->not
            ->toBeNull();

        app(
            VariantSpecificationWriter::class,
        )->set(
            $motherboardItem->variant,
            'socket',
            'lga1700',
        );

        $run = app(
            ConfigurationValidator::class,
        )->validate($configuration);

        $socketResult =
            $run
                ->results
                ->firstWhere(
                    'rule_key',
                    'cpu_motherboard_socket',
                );

        expect($run->status)
            ->toBe(ValidationRunStatus::Failed)

            ->and($socketResult)
            ->not
            ->toBeNull()

            ->and($socketResult->status)
            ->toBe(
                ValidationResultStatus::Failed,
            );

        expect(
            $configuration->fresh()->status,
        )->toBe(ConfigurationStatus::Invalid);
    },
);
