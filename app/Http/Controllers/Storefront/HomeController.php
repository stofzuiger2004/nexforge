<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\FeaturedSystemResource;
use App\Models\PriceList;
use App\Models\System;
use App\Services\Storefront\SystemAvailabilityService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(
        SystemAvailabilityService $availability,
    ): Response {
        $priceList = PriceList::query()
            ->where('currency', 'EUR')
            ->default()
            ->available()
            ->firstOrFail();

        $systems = System::query()
            ->published()
            ->featured()

            /*
             * Do not show a system that has no price in the
             * currently selected storefront price list.
             */
            ->whereHas(
                'prices',
                static fn ($query) => $query->where(
                    'price_list_id',
                    $priceList->id,
                ),
            )

            ->with([
                'prices' => static fn ($query) => $query->where(
                    'price_list_id',
                    $priceList->id,
                ),

                'prices.priceList',

                'images' => static fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('id'),

                'components' => static fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),

                'components.variant.product.brand',

                'components.variant.inventoryItems' => static fn ($query) => $query->where(
                    'is_active',
                    true,
                ),

                'components.variant.inventoryItems.warehouse',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->get();

        $systems->each(
            static function (
                System $system,
            ) use ($availability): void {
                /*
                 * This is a temporary presentation attribute.
                 * It is not written to the systems table.
                 */
                $system->setAttribute(
                    'available_builds',
                    $availability->availableBuilds(
                        $system,
                    ),
                );
            },
        );

        return Inertia::render('storefront/index', [
            'featuredSystems' => FeaturedSystemResource::collection(
                $systems,
            )->resolve(request()),
        ]);
    }
}
