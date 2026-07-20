<?php

declare(strict_types=1);

namespace App\Services\Systems;

Use App\Enums\ComponentSlot;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\ProductVariant;
use App\Models\System;
use App\Models\SystemComponent;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SystemDefaultComponentService{
    public function __construct(private readonly SystemPresetCompatibilityService $compatibility){}
    public function assign(System $system, ComponentSlot $slot, ProductVariant $variant, int $quantity = 1, bool $isRequired = true, bool $isReplaceable = true): SystemComponent{
        return DB::transaction(function () use($system,$slot,$variant,$quantity,$isRequired,$isReplaceable):SystemComponent{
            $lockedSystem = System::query()->lockForUpdate()->findOrFail($system->getKey());
            $lockedVariant = ProductVariant::query()->with(['product.category','prices.priceList','specificationValues','specificationOptions'])->lockForUpdate()->findOrFail($variant->getKey());
            $product = $variant->product;
            $category = $product->category;

            if($category === null){
                throw new DomainException('The selected variant has no product category.');
            }
            if($category->slug !== $slot->categorySlug()){
                throw new DomainException(sprintf(
                    '%s belongs to the "%s" category and cannot be assigned to the %s slot',
                    $lockedVariant->displayName(),
                    $category->name,
                    $slot->label()
                ));
            }
            if($lockedVariant->status !== VariantStatus::Active){
                throw new DomainException('The selected variant is not active.');
            }
            if(!$product->is_configurable){
                throw new DomainException('The selected product is not enabled for the configurator.');
            }
            if($product->published_at === null || $product->published_at->isFuture()){
                throw new DomainException('The selected product is not published.');
            }
            if($quantity < 1){
                throw new DomainException('The component quantity must be at least one.');
            }
            if(!$slot->supportsQuantity() && $quantity !== 1){
                throw new DomainException(sprintf(
                    'The %s slot only supports a quantity of one.',
                    $slot->label()
                ));
            }
            if($lockedSystem->prices->isEmpty()){
                throw new DomainException('The system has no system price. Add a system price before assigning defaults.');
            }

            $requiredPriceListIds = $lockedSystem->prices->pluck('price_list_id')->map(static fn($id): int => (int) $id)->unique()->values();
            $variantPriceListIds = $lockedVariant->prices->pluck('price_list_id')->map(static fn ($id): int => (int) $id)->unique();
            $missingPriceListIds = $requiredPriceListIds->diff($variantPriceListIds)->values();

            if($missingPriceListIds->isNotEmpty()){
                $missingNames = $lockedSystem->prices->whereIn('price_list_id',$missingPriceListIds->all())->map(static fn ($price): string => $price->priceList->name)->unique()->values();
                throw new DomainException(sprintf(
                    'The selected variant is missing prices for: %s',
                    $missingNames->implode(', ')
                ));
            }
            
            $this->compatibility->assertReplacementIsCompatible(
                system: $lockedSystem,
                slot: $slot,
                variant: $lockedVariant,
                quantity: $quantity
            );

            $existing = SystemComponent::query()->where('system_id',$lockedSystem->id)->where('slot',$slot->value)->lockForUpdate()->get();

            if($existing->count() > 1){
                throw new DomainException(sprintf(
                    'The system currently has multiple defaults for %s. Resolve the duplicate system components rows first.',
                    $slot->label()
                ));
            }

            /** @var SystemComponent|null $current */
            $current = $existing->first();
            if($current === null){
                return SystemComponent::query()->updateOrCreate([
                    'system_id'=>$lockedSystem->id,
                    'product_variant_id'=>$lockedVariant->id,
                    'slot'=>$slot,
                    'quantity'=>$quantity,
                    'is_required'=>$isRequired,
                    'is_replaceable'=>$isReplaceable,
                    'sort_order'=>$slot->sortOrder()
                ]);
            }

            $current->forceFill([
                'product_variant_id'=>$lockedVariant->id,
                'quantity'=>$quantity,
                'is_required'=>$isRequired,
                'is_replaceable'=>$isReplaceable,
                'sort_order'=>$slot->sortOrder()
            ])->saveOrFail();

            return $current->refresh();
            
            },
        attempts:3
        );
    }
}