<?php

declare(strict_types=1);

use App\Models\PriceList;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VariantPrice;
use App\Models\VariantPriceHistory;
use Database\Seeders\AdminAuthorizationSeeder;
use Database\Seeders\CatalogReferenceSeeder;
use Database\Seeders\DemoStorefrontSeeder;
use Database\Seeders\PriceListSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        PriceListSeeder::class,
        CatalogReferenceSeeder::class,
        DemoStorefrontSeeder::class,
        AdminAuthorizationSeeder::class,
    ]);

    $this->administrator = User::factory()
        ->create([
            'email_verified_at' => now(),
        ]);

    $this->administrator->assignRole(
        'administrator',
    );

    $this->variant = ProductVariant::query()
        ->where('sku', 'GPU-RTX-4060')
        ->firstOrFail();

    $this->priceList = PriceList::query()
        ->where('code', 'retail-eur')
        ->firstOrFail();

    $this->price = VariantPrice::query()
        ->where(
            'product_variant_id',
            $this->variant->id,
        )
        ->where(
            'price_list_id',
            $this->priceList->id,
        )
        ->firstOrFail();
});

test('an administrator can change a price with an audit record', function (): void {
    $oldAmount = $this->price
        ->amount_in_cents;

    $this->actingAs($this->administrator)
        ->patch(
            route(
                'admin.variant-prices.update',
                [
                    'variant' => $this->variant,
                    'priceList' => $this
                        ->priceList,
                ],
            ),
            [
                'amount' => '499.95',
                'compare_at_amount' => '549.95',
                'reason' => 'Updated supplier pricing.',
                'expected_lock_version' => $this
                    ->price
                    ->lock_version,
            ],
        )
        ->assertRedirect();

    $price = $this->price->fresh();

    expect($price->amount_in_cents)
        ->toBe(49995)
        ->and(
            $price
                ->compare_at_amount_in_cents,
        )
        ->toBe(54995)
        ->and($price->lock_version)
        ->toBe(
            $this->price->lock_version + 1,
        );

    $history = VariantPriceHistory::query()
        ->firstOrFail();

    expect($history->old_amount_in_cents)
        ->toBe($oldAmount)
        ->and($history->new_amount_in_cents)
        ->toBe(49995)
        ->and($history->actor_user_id)
        ->toBe($this->administrator->id)
        ->and($history->reason)
        ->toBe('Updated supplier pricing.');
});

test('stale price updates are rejected', function (): void {
    $submittedVersion = $this
        ->price
        ->lock_version;

    $this->price->increment(
        'lock_version',
    );

    $this->actingAs($this->administrator)
        ->patch(
            route(
                'admin.variant-prices.update',
                [
                    'variant' => $this->variant,
                    'priceList' => $this
                        ->priceList,
                ],
            ),
            [
                'amount' => '499.95',
                'compare_at_amount' => null,
                'reason' => 'Stale version test.',
                'expected_lock_version' => $submittedVersion,
            ],
        )
        ->assertSessionHasErrors('price');
});

test('compare at price must exceed the selling price', function (): void {
    $this->actingAs($this->administrator)
        ->patch(
            route(
                'admin.variant-prices.update',
                [
                    'variant' => $this->variant,
                    'priceList' => $this
                        ->priceList,
                ],
            ),
            [
                'amount' => '500.00',
                'compare_at_amount' => '499.00',
                'reason' => 'Invalid compare price test.',
                'expected_lock_version' => $this
                    ->price
                    ->lock_version,
            ],
        )
        ->assertSessionHasErrors('price');
});

test('price history records are immutable', function (): void {
    $history = VariantPriceHistory::query()
        ->create([
            'variant_price_id' => $this
                ->price
                ->id,

            'product_variant_id' => $this
                ->variant
                ->id,

            'price_list_id' => $this
                ->priceList
                ->id,

            'actor_user_id' => $this
                ->administrator
                ->id,

            'old_amount_in_cents' => 10000,
            'new_amount_in_cents' => 11000,

            'old_compare_at_amount_in_cents' => null,

            'new_compare_at_amount_in_cents' => null,

            'lock_version_before' => 1,
            'lock_version_after' => 2,

            'reason' => 'Immutable audit test.',

            'changed_at' => now(),
        ]);

    expect(
        fn () => $history
            ->forceFill([
                'reason' => 'Tampered',
            ])
            ->save(),
    )->toThrow(LogicException::class);

    expect(
        fn () => $history->delete(),
    )->toThrow(LogicException::class);
});