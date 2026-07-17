<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryReservationItem;
use App\Models\VariantPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin InventoryItem
 */
final class AdminInventoryDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request,
    ): array {
        $available = $this->availableQuantity();

        $canViewPrices = $request
            ->user()
            ?->can('prices.view') ?? false;

        return [
            'id' => $this->id,

            'href' => route(
                'admin.inventory.show',
                $this->resource,
            ),

            'update_url' => route(
                'admin.inventory.update',
                $this->resource,
            ),

            'adjustment_url' => route(
                'admin.inventory.adjustments.store',
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

            'last_counted_at' => $this
                ->last_counted_at
                ?->toIso8601String(),

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

            'can' => [
                'adjust_inventory' => $request
                    ->user()
                    ?->can('inventory.adjust')
                    ?? false,

                'manage_inventory' => $request
                    ->user()
                    ?->can('inventory.manage')
                    ?? false,

                'view_prices' => $canViewPrices,

                'manage_prices' => $request
                    ->user()
                    ?->can('prices.manage')
                    ?? false,
            ],

            'prices' => $canViewPrices
                ? $this
                    ->variant
                    ->prices
                    ->map(
                        fn (
                            VariantPrice $price,
                        ): array => [
                            'id' => $price->id,

                            'price_list' => [
                                'id' => $price
                                    ->priceList
                                    ->id,

                                'code' => $price
                                    ->priceList
                                    ->code,

                                'name' => $price
                                    ->priceList
                                    ->name,

                                'currency' => $price
                                    ->priceList
                                    ->currency,

                                'is_default' => $price
                                    ->priceList
                                    ->is_default,

                                'is_active' => $price
                                    ->priceList
                                    ->is_active,
                            ],

                            'amount_in_cents' => $price
                                ->amount_in_cents,

                            'compare_at_amount_in_cents' => $price
                                ->compare_at_amount_in_cents,

                            'lock_version' => $price
                                ->lock_version,

                            'update_url' => route(
                                'admin.variant-prices.update',
                                [
                                    'variant' => $this
                                        ->variant,

                                    'priceList' => $price
                                        ->priceList,
                                ],
                            ),
                        ],
                    )
                    ->values()
                    ->all()
                : null,

            'movements' => $this
                ->movements
                ->map(
                    static fn (
                        InventoryMovement $movement,
                    ): array => [
                        'public_id' => $movement
                            ->public_id,

                        'type' => $movement
                            ->type
                            ->value,

                        'on_hand_delta' => $movement
                            ->on_hand_delta,

                        'reserved_delta' => $movement
                            ->reserved_delta,

                        'on_hand_after' => $movement
                            ->on_hand_after,

                        'reserved_after' => $movement
                            ->reserved_after,

                        'reason' => $movement->reason,

                        'reference' => $movement
                            ->metadata['reference']
                            ?? null,

                        'actor' => $movement->actor
                            ? [
                                'id' => $movement
                                    ->actor
                                    ->id,

                                'name' => $movement
                                    ->actor
                                    ->name,

                                'email' => $movement
                                    ->actor
                                    ->email,
                            ]
                            : null,

                        'occurred_at' => $movement
                            ->occurred_at
                            ->toIso8601String(),
                    ],
                )
                ->values()
                ->all(),

            'reservations' => $this
                ->reservationItems
                ->map(
                    static fn (
                        InventoryReservationItem $item,
                    ): array => [
                        'public_id' => $item
                            ->reservation
                            ->public_id,

                        'status' => $item
                            ->reservation
                            ->status
                            ->value,

                        'quantity' => $item
                            ->quantity,

                        'outstanding_quantity' => $item
                            ->outstandingQuantity(),

                        'expires_at' => $item
                            ->reservation
                            ->expires_at
                            ?->toIso8601String(),

                        'order' => $item
                            ->reservation
                            ->order
                            ? [
                                'public_id' => $item
                                    ->reservation
                                    ->order
                                    ->public_id,

                                'order_number' => $item
                                    ->reservation
                                    ->order
                                    ->order_number,
                            ]
                            : null,
                    ],
                )
                ->values()
                ->all(),

            'form_tokens' => [
                'adjustment' => (string) Str::ulid(),
            ],
        ];
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
}