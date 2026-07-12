<?php

declare(strict_types=1);

namespace App\Http\Resources\Storefront;

use App\Enums\ComponentSlot;
use App\Models\SystemComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FeaturedSystemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = $this->prices->first();
        $image = $this->images->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->short_description,

            'price_in_cents' => $price?->amount_in_cents,
            'currency' => $price?->priceList?->currency,

            'image_url' => $image === null
                ? null
                : Storage::disk($image->disk)->url($image->path),

            'processor' => $this->componentName(
                ComponentSlot::Cpu,
            ),

            'graphics_card' => $this->componentName(
                ComponentSlot::GraphicsCard,
            ),

            'memory' => $this->componentName(
                ComponentSlot::Memory,
            ),

            'storage' => $this->componentName(
                ComponentSlot::PrimaryStorage,
            ),
        ];
    }

    private function componentName(
        ComponentSlot $slot,
    ): ?string {
        /** @var SystemComponent|null $component */
        $component = $this->components->first(
            static fn (SystemComponent $component): bool => $component->slot === $slot,
        );

        return $component?->variant?->product?->name;
    }
}
