<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\ComponentSlot;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Http\Resources\Admin\AdminSystemPresetResource;
use App\Http\Resources\Admin\AdminSystemVariantOptionResource;
use App\Models\ProductVariant;
use App\Models\System;
use App\Models\SystemComponent;
use App\Models\SystemPrice;
use App\Services\Systems\SystemPresetCompatibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AdminSystemPresetPageDataService
{
    public function __construct(
        private readonly SystemPresetCompatibilityService $compatibility,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        System $system,
        Request $request,
    ): array {
        $system->load([
            'prices.priceList',
            'components.variant.product.brand',
            'components.variant.product.category',
            'components.variant.prices.priceList',
            'components.variant.inventoryItems.warehouse',
            'components.variant.specificationValues',
            'components.variant.specificationOptions',
        ]);

        $categorySlugs = collect(
            ComponentSlot::cases(),
        )
            ->map(
                static fn (
                    ComponentSlot $slot,
                ): string => $slot->categorySlug(),
            )
            ->unique()
            ->values();

        $systemPriceListIds = $system
            ->prices
            ->pluck('price_list_id')
            ->map(
                static fn ($id): int => (int) $id,
            )
            ->unique()
            ->values();

        $candidates = ProductVariant::query()
            ->where(
                'status',
                VariantStatus::Active->value,
            )
            ->whereHas(
                'product',
                static function (
                    Builder $query,
                ) use ($categorySlugs): void {
                    $query
                        ->where(
                            'status',
                            ProductStatus::Active->value,
                        )
                        ->where(
                            'is_configurable',
                            true,
                        )
                        ->whereNotNull(
                            'published_at',
                        )
                        ->where(
                            'published_at',
                            '<=',
                            now(),
                        )
                        ->whereHas(
                            'category',
                            static fn (
                                Builder $query,
                            ) => $query->whereIn(
                                'slug',
                                $categorySlugs->all(),
                            ),
                        );
                },
            )
            ->with([
                'product.brand',
                'product.category',
                'prices' => static fn (
                    $query,
                ) => $query
                    ->whereIn(
                        'price_list_id',
                        $systemPriceListIds->all(),
                    )
                    ->with('priceList'),
                'inventoryItems.warehouse',
            ])
            ->get();

        /*
         * Include legacy/current selections even if they are no longer
         * eligible. The UI can display the current value and explain why
         * it cannot be selected again.
         */
        $candidates = $candidates
            ->concat(
                $system
                    ->components
                    ->pluck('variant'),
            )
            ->unique('id')
            ->values();

        $candidatesByCategory = $candidates
            ->groupBy(
                static fn (
                    ProductVariant $variant,
                ): string => $variant
                    ->product
                    ->category
                    ->slug,
            );

        $componentsBySlot = $system
            ->components
            ->keyBy(
                static fn (
                    SystemComponent $component,
                ): string => $component
                    ->slot
                    ->value,
            );

        $slots = collect(
            ComponentSlot::cases(),
        )
            ->sortBy(
                static fn (
                    ComponentSlot $slot,
                ): int => $slot->sortOrder(),
            )
            ->map(
                function (
                    ComponentSlot $slot,
                ) use (
                    $request,
                    $system,
                    $systemPriceListIds,
                    $candidatesByCategory,
                    $componentsBySlot,
                ): array {
                    $current = $componentsBySlot->get(
                        $slot->value,
                    );

                    /** @var Collection<int, ProductVariant> $slotCandidates */
                    $slotCandidates = $candidatesByCategory->get(
                        $slot->categorySlug(),
                        collect(),
                    );

                    $options = $slotCandidates
                        ->sortBy(
                            static fn (
                                ProductVariant $variant,
                            ): string => mb_strtolower(
                                $variant->displayName(),
                            ),
                        )
                        ->map(
                            function (
                                ProductVariant $variant,
                            ) use (
                                $request,
                                $slot,
                                $current,
                                $systemPriceListIds,
                            ): array {
                                $data = (
                                    new AdminSystemVariantOptionResource(
                                        $variant,
                                    )
                                )->resolve($request);

                                $missingPriceListIds = $systemPriceListIds
                                    ->diff(
                                        $variant
                                            ->prices
                                            ->pluck(
                                                'price_list_id',
                                            )
                                            ->map(
                                                static fn ($id): int => (int) $id,
                                            ),
                                    )
                                    ->values();

                                $disabledReason = $this
                                    ->disabledReason(
                                        variant: $variant,
                                        slot: $slot,
                                        missingPriceListIds: $missingPriceListIds,
                                    );

                                return [
                                    ...$data,
                                    'selected' => $current !== null
                                        && (int) $current
                                            ->product_variant_id
                                            === (int) $variant->id,
                                    'disabled_reason' => $disabledReason,
                                ];
                            },
                        )
                        ->values()
                        ->all();

                    return [
                        'value' => $slot->value,
                        'label' => $slot->label(),
                        'description' => $slot->description(),
                        'allows_multiple' => $slot->allowsMultiple(),
                        'supports_quantity'=>$slot->supportsQuantity(),
                        'current' => $current === null
                            ? null
                            : [
                                'component_id' => $current->id,
                                'variant_id' => $current
                                    ->product_variant_id,
                                'quantity' => $current->quantity,
                                'is_required' => $current
                                    ->is_required,
                                'is_replaceable' => $current
                                    ->is_replaceable,
                            ],
                        'options' => $options,
                        'update_url' => route(
                            'admin.systems.components.update',
                            [
                                'system' => $system,
                                'slot' => $slot->value,
                            ],
                        ),
                    ];
                },
            )
            ->values()
            ->all();

        return [
            'system' => (
                new AdminSystemPresetResource(
                    $system,
                )
            )->resolve($request),
            'slots' => $slots,
            'compatibility' => $this
                ->compatibility
                ->evaluate($system),
            'pricing' => $this->pricingSummary(
                $system,
            ),
            'can' => [
                'manage_prices' => $request
                    ->user()
                    ?->can('prices.manage')
                    ?? false,
            ],
        ];
    }

    /**
     * @param Collection<int, int> $missingPriceListIds
     */
    private function disabledReason(
        ProductVariant $variant,
        ComponentSlot $slot,
        Collection $missingPriceListIds,
    ): ?string {
        $product = $variant->product;

        if (
            $product->category->slug
            !== $slot->categorySlug()
        ) {
            return 'The component category does not match this slot.';
        }

        if ($variant->status !== VariantStatus::Active) {
            return 'The variant is not active.';
        }

        if ($product->status !== ProductStatus::Active) {
            return 'The product is not active.';
        }

        if (! $product->is_configurable) {
            return 'The product is not enabled for the configurator.';
        }

        if (
            $product->published_at === null
            || $product->published_at->isFuture()
        ) {
            return 'The product is not published.';
        }

        if ($missingPriceListIds->isNotEmpty()) {
            return 'The variant is missing one or more prices used by this system.';
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pricingSummary(
        System $system,
    ): array {
        return $system
            ->prices
            ->map(
                static function (
                    SystemPrice $systemPrice,
                ) use ($system): array {
                    $componentSubtotal = 0;
                    $missingPriceSkus = [];

                    foreach (
                        $system->components
                        as $component
                    ) {
                        $variantPrice = $component
                            ->variant
                            ->prices
                            ->firstWhere(
                                'price_list_id',
                                $systemPrice
                                    ->price_list_id,
                            );

                        if ($variantPrice === null) {
                            $missingPriceSkus[] = $component
                                ->variant
                                ->sku;

                            continue;
                        }

                        $componentSubtotal += $variantPrice
                            ->amount_in_cents
                            * $component->quantity;
                    }

                    $complete = $missingPriceSkus === [];

                    return [
                        'system_price_id' => $systemPrice
                            ->id,
                        'price_list_id' => $systemPrice
                            ->price_list_id,
                        'price_list_name' => $systemPrice
                            ->priceList
                            ->name,
                        'currency' => $systemPrice
                            ->priceList
                            ->currency,
                        'system_price_in_cents' => $systemPrice
                            ->amount_in_cents,
                        'compare_at_amount_in_cents' => $systemPrice
                            ->compare_at_amount_in_cents,
                        'lock_version' => $systemPrice
                            ->lock_version,
                        'update_url' => route(
                            'admin.systems.prices.update',
                            [
                                'system' => $system,
                                'priceList' => $systemPrice
                                    ->priceList,
                            ],
                        ),
                        'component_subtotal_in_cents' => $complete
                            ? $componentSubtotal
                            : null,
                        'package_adjustment_in_cents' => $complete
                            ? $systemPrice
                                ->amount_in_cents
                                - $componentSubtotal
                            : null,
                        'missing_price_skus' => array_values(
                            array_unique(
                                $missingPriceSkus,
                            ),
                        ),
                    ];
                },
            )
            ->values()
            ->all();
    }
}
