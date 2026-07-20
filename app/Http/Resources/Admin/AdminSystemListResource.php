<?php

declare(strict_types=1);
namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSystemListResource extends JsonResource
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
            'component_count' => (int) ($this->components_count ?? 0),
            'prices' => $this->prices->map(static fn ($price): array => [
                        'price_list_id' => $price->price_list_id,
                        'price_list_name' => $price->priceList->name,
                        'currency' => $price->priceList->currency,
                        'amount_in_cents' => $price->amount_in_cents
                    ])->values()->all(),
            'edit_url' => route('admin.systems.edit',$this->resource),
            'storefront_url' => route('gaming-pcs.show',$this->slug)
        ];
    }
}
