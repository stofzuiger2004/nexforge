<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\InventoryItem;
use App\Models\VariantPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InventoryItem
 */
final class AdminInventoryListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request,
    ): array {
        $available = $this->availableQuantity();

        $image = $this
            ->variant
            ->product
            ->images
            ->firstWhere(
                'is_primary',
                true,
            );

        $data = [
            'id' => $this->id,

            'href' => route(
                'admin.inventory.show',
                $this->resource,
            ),

            'product' => [
                'name' => $this
                    ->variant
                    ->product
                    ->name,

                'brand' => $this
                    ->variant
                    ->product
                    ->brand
                    ?->name,
            ],

            'variant' => [
                'id' => $this->variant->id,
                'name' => $this
                    ->variant
                    ->displayName(),
                'sku' => $this->variant->sku,
            ],

            'warehouse' => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ],

            'bin_location' => $this->bin_location,

            'stock' => [
                'quantity_on_hand' => $this
                    ->quantity_on_hand,

                'quantity_reserved' => $this
                    ->quantity_reserved,

                'available' => $available,

                'reservable' => $this
                    ->reservableQuantity(),

                'safety_stock' => $this
                    ->safety_stock,

                'reorder_point' => $this
                    ->reorder_point,

                'is_active' => $this->is_active,

                'state' => $this->stockState(
                    $available,
                ),

                'lock_version' => $this
                    ->lock_version,
            ],
            'image' => $image === null ? null :
            [
                'url'=>$image->url,
                'alt'=>$image->alt_text ?: $this->variant->product->name
            ]
        ];

        if (
            $request->user()?->can(
                'prices.view',
            )
        ) {
            $price = $this->defaultPrice();

            $data['price'] = $price === null
                ? null
                : [
                    'amount_in_cents' => $price
                        ->amount_in_cents,

                    'compare_at_amount_in_cents' => $price
                        ->compare_at_amount_in_cents,

                    'currency' => $price
                        ->priceList
                        ->currency,

                    'price_list_name' => $price
                        ->priceList
                        ->name,
                ];
        }

        return $data;
    }

    private function stockState(
        int $available,
    ): string {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($available <= 0) {
            return 'out_of_stock';
        }

        if (
            $this->reorder_point !== null
            && $available <= $this->reorder_point
        ) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    private function defaultPrice(): ?VariantPrice
    {
        return $this
            ->variant
            ->prices
            ->first(
                static fn (
                    VariantPrice $price,
                ): bool => $price
                    ->priceList
                    ->is_default,
            )
            ?? $this
                ->variant
                ->prices
                ->first();
    }
}