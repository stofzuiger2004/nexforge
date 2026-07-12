<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\FeaturedSystemResource;
use App\Models\PriceList;
use App\Models\System;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $priceList = PriceList::query()
            ->where('currency', 'EUR')
            ->default()
            ->available()
            ->firstOrFail();

        $systems = System::query()
            ->published()
            ->featured()
            ->with([
                'components' => static fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('id'),

                'components.variant.product',

                'images' => static fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order'),

                'prices' => static fn ($query) => $query
                    ->where('price_list_id', $priceList->id),

                'prices.priceList',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->get();

        return Inertia::render('storefront/index', [
            'featuredSystems' => FeaturedSystemResource::collection($systems)
                ->resolve(request()),
        ]);
    }
}
