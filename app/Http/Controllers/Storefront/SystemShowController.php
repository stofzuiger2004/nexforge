<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\SystemDetailResource;
use App\Models\PriceList;
use App\Models\System;
use App\Services\Storefront\SystemAvailabilityService;
use Inertia\Inertia;
use Inertia\Response;

class SystemShowController extends Controller
{
    public function __invoke(string $slug,SystemAvailabilityService $availability): Response {
        $priceList = PriceList::query()->where('currency', 'EUR')->default()->available()->firstOrFail();
        $system = System::query()->published()->where('slug', $slug)->whereHas('prices',static fn ($query) =>$query->where('price_list_id',$priceList->id))
            ->with([
                'prices' => static fn ($query) =>
                    $query->where(
                        'price_list_id',
                        $priceList->id,
                    ),
                'prices.priceList',
                'images' => static fn ($query) =>
                    $query
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                'components' => static fn ($query) =>
                    $query
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                'components.variant.product.brand',
                'components.variant.product.category',
                'components.variant.specificationValues.specification',
                'components.variant.specificationOptions.specification',
                'components.variant.inventoryItems' =>
                    static fn ($query) =>
                        $query->where(
                            'is_active',
                            true,
                        ),
                'components.variant.inventoryItems.warehouse',
            ])
            ->firstOrFail();

        /*
         * Presentation-only attribute. It is not persisted.
         */
        $system->setAttribute(
            'available_builds',
            $availability->availableBuilds(
                $system,
            ),
        );

        return Inertia::render(
            'storefront/systems/show',
            [
                'system' => (
                    new SystemDetailResource(
                        $system,
                    )
                )->resolve(request()),
            ],
        );
    }
}