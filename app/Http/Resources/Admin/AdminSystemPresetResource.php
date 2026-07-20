<?php

declare(strict_types=1);
namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Models\SystemComponent;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSystemPresetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'is_configurable' => $this->is_configurable,
            'published_at' => $this->published_at?->toIso8601String(),
            'prices' => $this->prices->map(static fn ($price): array => [
                        'price_list_id' => $price->price_list_id,
                        'price_list_name' => $price->priceList->name,
                        'currency' => $price->priceList->currency,
                        'amount_in_cents' => $price->amount_in_cents,
                    ])->values()->all(),
            'components' => $this->components->map(static fn (SystemComponent $component): array => [
                        'id' => $component->id,
                        'slot' => $component->slot->value,
                        'quantity' => $component->quantity,
                        'is_required' => $component->is_required,
                        'is_replaceable' => $component->is_replaceable,
                        'variant' => [
                            'id' => $component->variant->id,
                            'sku' => $component->variant->sku,
                            'name' => $component->variant->displayName(),
                            'product_name' => $component->variant->product->name,
                            'brand' => $component->variant->product->brand?->name,
                            'category' => $component->variant->product->category?->name,
                        ],
                    ],
                )->values()->all(),
            'index_url' => route('admin.systems.index'),
            'storefront_url' => route('gaming-pcs.show',$this->slug)
        ];
    }
}
