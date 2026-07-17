<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminInventoryFiltersRequest;
use App\Http\Resources\Admin\AdminInventoryListResource;
use App\Models\InventoryItem;
use App\Services\Admin\AdminInventoryFilterOptions;
use App\Services\Admin\AdminInventoryQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InventoryIndexController extends Controller
{
    public function __invoke(
        AdminInventoryFiltersRequest $request,
        AdminInventoryQueryService $queryService,
        AdminInventoryFilterOptions $filterOptions,
    ): Response {
        $filters = $request->filters();

        $relations = [
            'warehouse',
            'variant.product.brand',
        ];

        if (
            $request->user()?->can(
                'prices.view',
            )
        ) {
            $relations[] = 'variant.prices.priceList';
        }

        $query = InventoryItem::query()
            ->with($relations);

        $items = $queryService
            ->apply(
                $query,
                $filters,
            )
            ->paginate(
                $filters['per_page'],
            )
            ->withQueryString();

        return Inertia::render(
            'admin/inventory/index',
            [
                'inventoryItems' => [
                    'data' => $items
                        ->getCollection()
                        ->map(
                            fn (
                                InventoryItem $item,
                            ): array => (
                                new AdminInventoryListResource(
                                    $item,
                                )
                            )->toArray($request),
                        )
                        ->values()
                        ->all(),

                    'pagination' => [
                        'current_page' => $items
                            ->currentPage(),

                        'from' => $items->firstItem(),

                        'last_page' => $items
                            ->lastPage(),

                        'per_page' => $items
                            ->perPage(),

                        'to' => $items->lastItem(),

                        'total' => $items->total(),

                        'links' => $items
                            ->linkCollection()
                            ->map(
                                static fn (
                                    array $link,
                                ): array => [
                                    'url' => $link['url'],
                                    'label' => $link['label'],
                                    'active' => $link['active'],
                                ],
                            )
                            ->all(),
                    ],
                ],

                'filters' => $filters,

                'filterOptions' => $filterOptions
                    ->all(),
            ],
        );
    }
}