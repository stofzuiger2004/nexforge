<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminInventoryDetailResource;
use App\Models\InventoryItem;
use App\Models\VariantPriceHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryShowController extends Controller
{
    public function __invoke(
        Request $request,
        InventoryItem $inventoryItem,
    ): Response {
        $inventoryItem->load([
            'warehouse',
            'variant.product.brand',
        ]);

        if (
            $request->user()?->can(
                'prices.view',
            )
        ) {
            $inventoryItem->load([
                'variant.prices.priceList',
            ]);
        }

        $inventoryItem->setRelation(
            'movements',
            $inventoryItem
                ->movements()
                ->with('actor:id,name,email')
                ->limit(50)
                ->get(),
        );

        $inventoryItem->setRelation(
            'reservationItems',
            $inventoryItem
                ->reservationItems()
                ->whereHas(
                    'reservation',
                    fn ($query) => $query
                        ->whereIn(
                            'status',
                            [
                                InventoryReservationStatus::Active
                                    ->value,

                                InventoryReservationStatus::Committed
                                    ->value,
                            ],
                        ),
                )
                ->with([
                    'reservation.order',
                ])
                ->get(),
        );

        $priceHistory = [];

        if (
            $request->user()?->can(
                'prices.view',
            )
        ) {
            $priceHistory = VariantPriceHistory::query()
                ->where(
                    'product_variant_id',
                    $inventoryItem
                        ->product_variant_id,
                )
                ->with([
                    'actor:id,name,email',
                    'priceList:id,name,currency',
                ])
                ->latest('changed_at')
                ->latest('id')
                ->limit(25)
                ->get()
                ->map(
                    static fn (
                        VariantPriceHistory $history,
                    ): array => [
                        'id' => $history->id,

                        'price_list' => [
                            'name' => $history
                                ->priceList
                                ->name,

                            'currency' => $history
                                ->priceList
                                ->currency,
                        ],

                        'old_amount_in_cents' => $history
                            ->old_amount_in_cents,

                        'new_amount_in_cents' => $history
                            ->new_amount_in_cents,

                        'old_compare_at_amount_in_cents' => $history
                            ->old_compare_at_amount_in_cents,

                        'new_compare_at_amount_in_cents' => $history
                            ->new_compare_at_amount_in_cents,

                        'reason' => $history->reason,

                        'actor' => $history->actor
                            ? [
                                'id' => $history
                                    ->actor
                                    ->id,

                                'name' => $history
                                    ->actor
                                    ->name,

                                'email' => $history
                                    ->actor
                                    ->email,
                            ]
                            : null,

                        'changed_at' => $history
                            ->changed_at
                            ->toIso8601String(),
                    ],
                )
                ->values()
                ->all();
        }

        return Inertia::render(
            'admin/inventory/show',
            [
                'inventoryItem' => (
                    new AdminInventoryDetailResource(
                        $inventoryItem,
                    )
                )->toArray($request),

                'priceHistory' => $priceHistory,

                'adjustmentOptions' => [
                    [
                        'value' => 'receipt',
                        'label' => 'Supplier receipt',
                    ],
                    [
                        'value' => 'correction_increase',
                        'label' => 'Correction increase',
                    ],
                    [
                        'value' => 'correction_decrease',
                        'label' => 'Correction decrease',
                    ],
                    [
                        'value' => 'damaged',
                        'label' => 'Damage / write-off',
                    ],
                    [
                        'value' => 'stock_count',
                        'label' => 'Physical stock count',
                    ],
                ],
            ],
        );
    }
}