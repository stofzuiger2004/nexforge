<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\CompatibilitySeverity;
use App\Enums\ConfigurationStatus;
use App\Enums\ProductStatus;
use App\Enums\ValidationResultStatus;
use App\Enums\VariantStatus;
use App\Models\CompatibilityRule;
use App\Models\Configuration;
use App\Models\ConfigurationItem;
use App\Models\ConfigurationValidationRun;
use App\Models\ProductVariant;
use App\Models\System;
use App\Models\SystemComponent;
use App\Services\Storefront\VariantPresentationService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class ConfiguratorPageDataService
{
    public function __construct(
        private readonly ConfigurationAvailabilityService $availability,

        private readonly ConfigurationOptionCompatibilityService $compatibility,

        private readonly VariantPresentationService $variantPresentation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        Configuration $configuration,
    ): array {
        $this->loadConfiguration(
            $configuration,
        );

        $system = $configuration->sourceSystem;

        if ($system === null) {
            throw new DomainException(
                'This configuration has no source system.',
            );
        }

        $rules = CompatibilityRule::query()
            ->active()
            ->with([
                'sourceSpecification',
                'targetSpecification',
            ])
            ->get();

        $latestValidationRun = $configuration
            ->validationRuns()
            ->with('results')
            ->latest('id')
            ->first();

        $groups = $system
            ->components
            ->groupBy(
                static fn (
                    SystemComponent $component,
                ): string => $component->slot->value,
            )
            ->map(
                fn (
                    Collection $components,
                ): array => $this->groupData(
                    $configuration,
                    $components,
                    $rules,
                    $latestValidationRun,
                ),
            )
            ->sortBy('sort_order')
            ->values();

        $basePrice = $system
            ->prices
            ->first()
            ?->amount_in_cents
            ?? $configuration->total_in_cents;

        $availableBuilds =
            $this->availability->availableBuilds(
                $configuration,
            );

        $validation = $this->validationData(
            $configuration,
            $latestValidationRun,
        );

        $image = $system->images->first();

        return [
            'configuration' => [
                'public_id' => $configuration->public_id,

                'name' => $configuration->name
                    ?? $system->name,

                'status' => $configuration->status->value,

                'version' => $configuration->version,

                'read_only' => ! $configuration
                    ->status
                    ->isEditable(),

                'updated_at' => $configuration
                    ->updated_at
                    ?->toIso8601String(),

                'source_system' => [
                    'id' => $system->id,
                    'name' => $system->name,
                    'slug' => $system->slug,

                    'details_url' => route(
                        'gaming-pcs.show',
                        $system,
                    ),

                    'image' => $image === null
                        ? null
                        : [
                            'url' => Storage::disk(
                                $image->disk,
                            )->url($image->path),

                            'alt' => $image->alt_text
                                ?: $system->name,
                        ],
                ],

                'pricing' => [
                    'currency' => $configuration->currency,

                    'base_price_in_cents' => $basePrice,

                    'component_change_in_cents' => $configuration
                        ->total_in_cents
                        - $basePrice,

                    'subtotal_in_cents' => $configuration
                        ->subtotal_in_cents,

                    'adjustment_total_in_cents' => $configuration
                        ->adjustment_total_in_cents,

                    'tax_in_cents' => $configuration
                        ->tax_in_cents,

                    'total_in_cents' => $configuration
                        ->total_in_cents,

                    'prices_include_tax' => $configuration
                        ->priceList
                        ->prices_include_tax,
                ],

                'availability' => $this->availabilityData(
                    $availableBuilds,
                ),

                'validation' => $validation,

                'can_review' => $configuration->status
                        === ConfigurationStatus::Valid
                    && $validation['is_current']
                    && $availableBuilds !== 0,
            ],

            'groups' => $groups->all(),
        ];
    }

    private function loadConfiguration(
        Configuration $configuration,
    ): void {
        $priceListId =
            $configuration->price_list_id;

        $configuration->load([
            'priceList',

            'items' => static fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),

            'items.variant.product.brand',
            'items.variant.product.category',
            'items.variant.images',
            'items.variant.product.images',

            'items.variant.specificationValues.specification',

            'items.variant.specificationOptions.specification',

            'items.variant.inventoryItems.warehouse',

            'adjustments',

            'sourceSystem.images',

            'sourceSystem.prices' => static fn ($query) => $query->where(
                'price_list_id',
                $priceListId,
            ),

            'sourceSystem.prices.priceList',

            'sourceSystem.components' => static fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),

            'sourceSystem.components.variant.product.brand',

            'sourceSystem.components.variant.product.category',

            'sourceSystem.components.variant.prices' => static fn ($query) => $query->where(
                'price_list_id',
                $priceListId,
            ),
        ]);
    }

    /**
     * @param  Collection<int, SystemComponent>  $components
     * @param  Collection<int, CompatibilityRule>  $rules
     * @return array<string, mixed>
     */
    private function groupData(
        Configuration $configuration,
        Collection $components,
        Collection $rules,
        ?ConfigurationValidationRun $latestValidationRun,
    ): array {
        /** @var SystemComponent $baseComponent */
        $baseComponent = $components->first();

        $slot = $baseComponent->slot;

        /** @var ConfigurationItem|null $selectedItem */
        $selectedItem = $configuration
            ->items
            ->first(
                static fn (
                    ConfigurationItem $item,
                ): bool => $item->slot === $slot,
            );

        $baseQuantity = max(1,(int) $baseComponent->quantity);
        $quantity = max(1,(int) ($selectedItem?->quantity ?? $baseQuantity));
        $basePrice = (int) ($baseComponent->variant->prices->first()?->amount_in_cents ?? 0);
        $baseLineTotal = $basePrice * $baseQuantity;
        $selectedLineTotal = $selectedItem?->line_total_in_cents ?? $baseLineTotal;
        $canEdit = $configuration->status->isEditable() && $baseComponent->is_replaceable && ! $slot->allowsMultiple();
        $variants = $canEdit
            ? $this->variantsForGroup(
                $baseComponent,
                $configuration->price_list_id,
                $selectedItem?->variant,
            )
            : collect(
                $selectedItem === null
                    ? []
                    : [$selectedItem->variant],
            );

        $options = $variants
            ->map(
                function (
                    ProductVariant $variant,
                ) use (
                    $configuration,
                    $rules,
                    $slot,
                    $selectedItem,
                    $quantity,
                    $selectedLineTotal,
                    $baseComponent,
                    $baseLineTotal,
                    $canEdit,
                ): array {
                    $price = $variant
                        ->prices
                        ->first();

                    if ($price === null) {
                        throw new DomainException(
                            sprintf(
                                'Variant "%s" has no configuration price.',
                                $variant->sku,
                            ),
                        );
                    }
                    $candidateLineTotal = $price->amount_in_cents * $quantity;

                    $isSelected =
                        $selectedItem
                            ?->product_variant_id
                        === $variant->id;

                    $compatibility =
                        $this->compatibility
                            ->evaluate(
                                $configuration,
                                $slot,
                                $variant,
                                $rules,
                            );

                    $availableBuilds =
                        $this->availability
                            ->availableBuilds(
                                $configuration,
                                $slot,
                                $variant,
                            );

                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,

                        'name' => $variant->displayName(),

                        'brand' => $variant
                            ->product
                            ->brand
                            ?->name,

                        'description' => $variant
                            ->product
                            ->short_description,

                        'image' => $this
                            ->variantPresentation
                            ->image($variant),

                        'specifications' => $this
                            ->variantPresentation
                            ->specifications(
                                $variant,
                                4,
                            ),

                        'is_selected' => $isSelected,

                        'is_base' => $baseComponent
                            ->product_variant_id
                            === $variant->id,

                        /*
                         * Compatibility issues are visible but do
                         * not prevent platform-transition changes.
                         */
                        'selectable' => $canEdit
                            && ! $isSelected,

                        'price' => [
                            'amount_in_cents' => $price->amount_in_cents,
                            'line_total_in_cents' => $candidateLineTotal,
                            'delta_from_current_in_cents' => $candidateLineTotal - $selectedLineTotal,
                            'delta_from_base_in_cents' => $candidateLineTotal - $selectedLineTotal,
                            'currency' => $configuration->currency,
                        ],

                        'availability' => $this->availabilityData(
                            $availableBuilds,
                        ),

                        'compatibility' => $compatibility,
                    ];
                },
            )
            ->values();

        $selectedOption = $options->firstWhere(
            'is_selected',
            true,
        );

        return [
            'slot' => $slot->value,
            'label' => $slot->label(),
            'description' => $slot->description(),
            'sort_order' => $baseComponent->sort_order,
            'quantity'=>$quantity,
            'is_required' => $baseComponent->is_required,
            'is_replaceable' => $baseComponent->is_replaceable,
            'can_edit' => $canEdit,
            'selection_mode' => $slot->allowsMultiple()
                    ? 'multiple'
                    : 'single',
            'selected' => $selectedOption,
            'selected_price_change_from_base_in_cents' => $selectedItem === null
                    ? 0
                    : $selectedLineTotal - $baseLineTotal,
            'issues' => $this->issuesForSlot(
                $latestValidationRun,
                $slot->value,
            ),
            'options' => $options->all(),
        ];
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function variantsForGroup(
        SystemComponent $baseComponent,
        int $priceListId,
        ?ProductVariant $selectedVariant,
    ): Collection {
        $categoryId = $baseComponent
            ->variant
            ->product
            ->category_id;

        $variants = ProductVariant::query()
            ->where(
                'status',
                VariantStatus::Active->value,
            )

            ->whereHas(
                'product',
                static function (
                    Builder $query,
                ) use ($categoryId): void {
                    $query
                        ->where(
                            'category_id',
                            $categoryId,
                        )
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
                        );
                },
            )

            ->whereHas(
                'prices',
                static fn (
                    Builder $query,
                ) => $query->where(
                    'price_list_id',
                    $priceListId,
                ),
            )

            ->with([
                'product.brand',
                'product.category',
                'product.images',
                'images',

                'prices' => static fn ($query) => $query->where(
                    'price_list_id',
                    $priceListId,
                ),

                'specificationValues.specification',

                'specificationOptions.specification',

                'inventoryItems.warehouse',
            ])
            ->get();

        if (
            $selectedVariant !== null
            && ! $variants->contains(
                'id',
                $selectedVariant->id,
            )
        ) {
            $selectedVariant->load([
                'product.brand',
                'product.category',
                'product.images',
                'images',

                'prices' => static fn ($query) => $query->where(
                    'price_list_id',
                    $priceListId,
                ),

                'specificationValues.specification',

                'specificationOptions.specification',

                'inventoryItems.warehouse',
            ]);

            $variants->push(
                $selectedVariant,
            );
        }

        return $variants
            ->sort(
                static function (
                    ProductVariant $left,
                    ProductVariant $right,
                ): int {
                    $leftPrice =
                        $left
                            ->prices
                            ->first()
                            ?->amount_in_cents
                        ?? PHP_INT_MAX;

                    $rightPrice =
                        $right
                            ->prices
                            ->first()
                            ?->amount_in_cents
                        ?? PHP_INT_MAX;

                    $priceComparison =
                        $leftPrice <=> $rightPrice;

                    if ($priceComparison !== 0) {
                        return $priceComparison;
                    }

                    return strcasecmp(
                        $left->displayName(),
                        $right->displayName(),
                    );
                },
            )
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function validationData(
        Configuration $configuration,
        ?ConfigurationValidationRun $run,
    ): array {
        $isCurrent =
            $run !== null
            && $run->configuration_version
                === $configuration->version;

        $failedResults = $run === null
            ? collect()
            : $run
                ->results
                ->filter(
                    static fn ($result): bool => $result->status
                        === ValidationResultStatus::Failed,
                );

        $errors = $failedResults
            ->filter(
                static fn ($result): bool => $result->severity
                    === CompatibilitySeverity::Error,
            )
            ->map(
                static fn ($result): array => [
                    'rule_key' => $result->rule_key,

                    'message' => $result->message
                        ?? $result->rule_name,

                    'context' => $result->context ?? [],
                ],
            )
            ->values()
            ->all();

        $warnings = $failedResults
            ->reject(
                static fn ($result): bool => $result->severity
                    === CompatibilitySeverity::Error,
            )
            ->map(
                static fn ($result): array => [
                    'rule_key' => $result->rule_key,

                    'message' => $result->message
                        ?? $result->rule_name,

                    'context' => $result->context ?? [],
                ],
            )
            ->values()
            ->all();

        return [
            'status' => $configuration->status->value,

            'label' => match (
                $configuration->status
            ) {
                ConfigurationStatus::Draft => 'Validation required',

                ConfigurationStatus::Valid => 'Configuration compatible',

                ConfigurationStatus::Invalid => 'Compatibility issues found',

                ConfigurationStatus::Converted => 'Configuration converted',

                ConfigurationStatus::Expired => 'Configuration expired',

                ConfigurationStatus::ReadyForCheckout => 'Ready For Checkout'
            },

            'is_current' => $isCurrent,

            'completed_at' => $run
                ?->completed_at
                ?->toIso8601String(),

            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<int, array{
     *     rule_key: string,
     *     severity: string,
     *     message: string
     * }>
     */
    private function issuesForSlot(
        ?ConfigurationValidationRun $run,
        string $slot,
    ): array {
        if ($run === null) {
            return [];
        }

        return $run
            ->results
            ->filter(
                static fn ($result): bool => $result->status
                    === ValidationResultStatus::Failed,
            )
            ->filter(
                static function (
                    $result,
                ) use ($slot): bool {
                    $context =
                        $result->context ?? [];

                    return (
                        $context['source_slot']
                        ?? null
                    ) === $slot
                    || (
                        $context['target_slot']
                        ?? null
                    ) === $slot;
                },
            )
            ->map(
                static fn ($result): array => [
                    'rule_key' => $result->rule_key,

                    'severity' => $result
                        ->severity
                        ->value,

                    'message' => $result->message
                        ?? $result->rule_name,
                ],
            )
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     status: string,
     *     label: string,
     *     available_builds: int|null
     * }
     */
    private function availabilityData(
        ?int $availableBuilds,
    ): array {
        return match (true) {
            $availableBuilds === null => [
                'status' => 'made_to_order',
                'label' => 'Built to order',
                'available_builds' => null,
            ],

            $availableBuilds === 0 => [
                'status' => 'unavailable',
                'label' => 'Complete build currently unavailable',
                'available_builds' => 0,
            ],

            $availableBuilds <= 3 => [
                'status' => 'limited',
                'label' => 'Limited component stock',
                'available_builds' => $availableBuilds,
            ],

            default => [
                'status' => 'available',
                'label' => 'Available to configure',
                'available_builds' => $availableBuilds,
            ],
        };
    }
}
