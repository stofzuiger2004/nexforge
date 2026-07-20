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
    public function assign(System $system, ComponentSlot $slot, ProductVariant $variant, int $quantity = 1, bool $isRequired = true, bool $isReplaceable = true): SystemComponent{
        return DB::transaction(function () use($system,$slot,$variant,$quantity,$isRequired,$isReplaceable):SystemComponent{
            $lockedSystem = System::query()->lockForUpdate()->findOrFail($system->getKey());
            $variant->loadMissing(['product.category']);
            $product = $variant->product;

            if($product->category->slug !== $slot->categorySlug()){
                throw new DomainException(sprintf('A component from category "%s" cannot be assigned to the "%s" slot.',$product->category->slug, $slot->value,));
            }

            if($variant->status !== VariantStatus::Active){
                throw new DomainException('The selected variant is not active');
            }

            if(!$product->is_configurable){
                throw new DomainException('The selected product is not available in the configurator.');
            }

            if($product->published_at === null || $product->published_at->isFuture()){
                throw new DomainException('The selected product is not published.');
            }

            if($quantity < 1){
                throw new DomainException('The component quantity must be at least one.');
            }

            return SystemComponent::query()->updateOrCreate([
                    'system_id'=>$lockedSystem->id,
                    'slot'=>$slot->value
                ],
                [
                    'product_variant_id'=>$variant->id,
                    'quantity'=>$quantity,
                    'is_required'=>$isRequired,
                    'is_replaceable'=>$isReplaceable,
                    'sort_order'=>$slot->sortOrder()
                ]
                );
            },
        attempts:3
        );
    }
}