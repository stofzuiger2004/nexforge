<?php

declare(strict_types=1);
namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSystemVariantOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usableInventoryItems = $this->inventoryItems->filter(static fn (InventoryItem $item): bool => $item->is_active
                    && $item->warehouse !== null
                    && $item->warehouse->is_active
                    && $item->warehouse->can_assemble_systems,
            );

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->displayName(),
            'product_name' => $this->product->name,
            'brand' => $this->product->brand?->name,
            'category' => [
                'name' => $this->product->category->name,
                'slug' => $this->product->category->slug
            ],
            'prices' => $this->prices->map(static fn ($price): array => [
                        'price_list_id' => $price->price_list_id,
                        'price_list_name' => $price->priceList->name,
                        'currency' => $price->priceList->currency,
                        'amount_in_cents' => $price->amount_in_cents,
                    ],
                )->values()->all(),
            'inventory' => [
                'tracked' => $this->track_inventory,
                'available' => $usableInventoryItems->max(static fn (InventoryItem $item): int => $item->availableQuantity()) ?? 0,
                'reservable' => $usableInventoryItems->max(static fn (InventoryItem $item): int => $item->reservableQuantity()) ?? 0
            ]
        ];
    }
}
