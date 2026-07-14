<?php

declare(strict_types=1);

namespace App\Http\Resources\Storefront;

use App\Enums\ComponentSlot;
use App\Models\System;
use App\Models\SystemComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin System
 */
class FeaturedSystemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $price = $this->prices->first();
        $image = $this->images->first();

        $rawAvailableBuilds = $this->resource
            ->getAttribute('available_builds');

        $availableBuilds = $rawAvailableBuilds === null
            ? null
            : (int) $rawAvailableBuilds;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,

            'description' => $this->short_description
                ?? $this->description,

            'is_configurable' => $this->is_configurable,

            'image' => $image === null
                ? null
                : [
                    'url' => Storage::disk(
                        $image->disk,
                    )->url($image->path),

                    'alt' => $image->alt_text
                        ?: $this->name,
                ],

            'price' => $price === null
                ? null
                : [
                    'amount_in_cents' => $price->amount_in_cents,

                    'compare_at_amount_in_cents' => $price
                        ->compare_at_amount_in_cents,

                    'currency' => $price->priceList->currency,
                ],

            'availability' => $this->availabilityData(
                $availableBuilds,
            ),

            'components' => [
            'processor' => $this->componentData(
                ComponentSlot::Cpu,
            ),

            'graphics_card' => $this->componentData(
                ComponentSlot::GraphicsCard,
            ),

            'memory' => $this->componentData(
                ComponentSlot::Memory,
            ),

            'storage' => $this->componentData(
                ComponentSlot::PrimaryStorage,
            ),
            ],
        ];
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
                'label' => 'Currently unavailable',
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

    /**
     * @return array{
     *     name: string,
     *     brand: string|null,
     *     quantity: int
     * }|null
     */
    private function componentData(
        ComponentSlot $slot,
    ): ?array {
        /** @var SystemComponent|null $component */
        $component = $this->components->first(
            static fn (SystemComponent $component): bool => $component->slot === $slot,
        );

        if ($component === null) {
            return null;
        }

        return [
            'name' => $component->variant->displayName(),

            'brand' => $component
                ->variant
                ->product
                ->brand
                ?->name,

            'quantity' => $component->quantity,
        ];
    }
}
