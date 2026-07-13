<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\ComponentSlot;
use App\Enums\ConfigurationStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Configuration;
use App\Models\ProductVariant;
use App\Models\VariantPrice;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ConfigurationEditor
{
    public function __construct(
        private readonly ConfigurationPricingService $pricing,
    ) {}

    public function setComponent(
        Configuration $configuration,
        ComponentSlot $slot,
        ProductVariant $variant,
        int $quantity = 1,
    ): Configuration {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'A configuration item quantity must be at least one.',
            );
        }

        return DB::transaction(
            function () use (
                $configuration,
                $slot,
                $variant,
                $quantity,
            ): Configuration {
                $lockedConfiguration =
                    $this->lockEditableConfiguration(
                        $configuration,
                    );

                $variant->loadMissing(
                    'product.category',
                );

                $this->assertVariantCanFillSlot(
                    $variant,
                    $slot,
                );

                $price = VariantPrice::query()
                    ->where(
                        'price_list_id',
                        $lockedConfiguration
                            ->price_list_id,
                    )
                    ->where(
                        'product_variant_id',
                        $variant->id,
                    )
                    ->first();

                if ($price === null) {
                    throw new DomainException(
                        'The selected component has no price in this configuration price list.',
                    );
                }

                /*
                 * CPU, motherboard, GPU, etc. allow one item.
                 * Secondary storage and case fans allow several.
                 */
                if (! $slot->allowsMultiple()) {
                    $lockedConfiguration
                        ->items()
                        ->where(
                            'slot',
                            $slot->value,
                        )
                        ->delete();
                }

                $lineTotal =
                    $price->amount_in_cents
                    * $quantity;

                $lockedConfiguration
                    ->items()
                    ->updateOrCreate(
                        [
                            'slot' => $slot->value,

                            'product_variant_id' => $variant->id,
                        ],
                        [
                            /*
                             * This is now a customer-selected
                             * component rather than the original
                             * system component.
                             */
                            'source_system_component_id' => null,

                            'quantity' => $quantity,

                            'sort_order' => $slot->sortOrder(),

                            'sku_snapshot' => $variant->sku,

                            'name_snapshot' => $variant->displayName(),

                            'unit_price_in_cents' => $price
                                ->amount_in_cents,

                            'line_total_in_cents' => $lineTotal,

                            'metadata' => null,
                        ],
                    );

                $this->invalidateValidation(
                    $lockedConfiguration,
                );

                $this->pricing->recalculate(
                    $lockedConfiguration,
                );

                return $lockedConfiguration->fresh([
                    'items.variant.product',
                    'adjustments',
                ]);
            },
            attempts: 3,
        );
    }

    public function removeComponent(
        Configuration $configuration,
        ComponentSlot $slot,
        ?ProductVariant $variant = null,
    ): Configuration {
        return DB::transaction(
            function () use (
                $configuration,
                $slot,
                $variant,
            ): Configuration {
                $lockedConfiguration =
                    $this->lockEditableConfiguration(
                        $configuration,
                    );

                $query = $lockedConfiguration
                    ->items()
                    ->where(
                        'slot',
                        $slot->value,
                    );

                /*
                 * For multi-value slots, passing a variant
                 * removes only that selected variant.
                 */
                if (
                    $slot->allowsMultiple()
                    && $variant !== null
                ) {
                    $query->where(
                        'product_variant_id',
                        $variant->id,
                    );
                }

                $deleted = $query->delete();

                if ($deleted > 0) {
                    $this->invalidateValidation(
                        $lockedConfiguration,
                    );

                    $this->pricing->recalculate(
                        $lockedConfiguration,
                    );
                }

                return $lockedConfiguration->fresh([
                    'items.variant.product',
                    'adjustments',
                ]);
            },
            attempts: 3,
        );
    }

    private function lockEditableConfiguration(
        Configuration $configuration,
    ): Configuration {
        $lockedConfiguration =
            Configuration::query()
                ->lockForUpdate()
                ->findOrFail(
                    $configuration->getKey(),
                );

        if (
            ! $lockedConfiguration
                ->status
                ->isEditable()
        ) {
            throw new DomainException(
                'This configuration can no longer be changed.',
            );
        }

        return $lockedConfiguration;
    }

    private function assertVariantCanFillSlot(
        ProductVariant $variant,
        ComponentSlot $slot,
    ): void {
        if (
            $variant->status
            !== VariantStatus::Active
        ) {
            throw new DomainException(
                'The selected product variant is not active.',
            );
        }

        if (
            $variant->product->status
            !== ProductStatus::Active
        ) {
            throw new DomainException(
                'The selected product is not active.',
            );
        }

        if (
            $variant->product->published_at === null
            || $variant
                ->product
                ->published_at
                ->isFuture()
        ) {
            throw new DomainException(
                'The selected product is not published.',
            );
        }

        if (
            $variant
                ->product
                ->category
                ->slug
            !== $slot->categorySlug()
        ) {
            throw new DomainException(sprintf(
                'A product from category "%s" cannot fill the "%s" slot.',
                $variant
                    ->product
                    ->category
                    ->slug,
                $slot->value,
            ));
        }
    }

    private function invalidateValidation(
        Configuration $configuration,
    ): void {
        $configuration->forceFill([
            'status' => ConfigurationStatus::Draft,

            'version' => $configuration->version + 1,

            'validated_at' => null,
            'last_activity_at' => now(),
        ])->save();
    }
}
