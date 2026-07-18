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
use App\Models\Warehouse;
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

        $perPage = (int) $request->integer('per_page',25);
        $perPage = in_array($perPage,[15, 25, 50, 100],true) ? $perPage : 25;

        $query = InventoryItem::query()
            ->with($relations);

        $inventoryItems = $query->paginate($perPage)->withQueryString();
        $user = $request->user();
        $createComponentUrl = $user !== null && $user->can('catalog.manage')
        ? route('admin.inventory.components.create')
        : null;

        $warehouseOptions = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
            ])
            ->map(
                static fn (Warehouse $warehouse): array => [
                    'value' => (string) $warehouse->id,
                    'label' => sprintf(
                        '%s (%s)',
                        $warehouse->name,
                        $warehouse->code,
                    ),
                ],
            )
            ->values()
            ->all();
        
        $stateOptions = [
            [
                'value' => 'in_stock',
                'label' => 'In stock',
            ],
            [
                'value' => 'low_stock',
                'label' => 'Low stock',
            ],
            [
                'value' => 'out_of_stock',
                'label' => 'Out of stock',
            ],
            [
                'value' => 'inactive',
                'label' => 'Inactive',
            ],
        ];

        $sortOptions = [
            [
                'value' => 'name',
                'label' => 'Name A–Z',
            ],
            [
                'value' => 'name_desc',
                'label' => 'Name Z–A',
            ],
            [
                'value' => 'sku',
                'label' => 'SKU',
            ],
            [
                'value' => 'available_asc',
                'label' => 'Availability: low first',
            ],
            [
                'value' => 'available_desc',
                'label' => 'Availability: high first',
            ],
        ];
        $perPageOptions = [
            [
                'value' => '15',
                'label' => '15',
            ],
            [
                'value' => '25',
                'label' => '25',
            ],
            [
                'value' => '50',
                'label' => '50',
            ],
            [
                'value' => '100',
                'label' => '100',
            ],
        ];

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
            'data' => AdminInventoryListResource::collection(
                $inventoryItems->getCollection(),
            )->resolve($request),

            'meta' => [
                'current_page' => $inventoryItems
                    ->currentPage(),

                'from' => $inventoryItems->firstItem(),

                'last_page' => $inventoryItems
                    ->lastPage(),

                'per_page' => $inventoryItems
                    ->perPage(),

                'to' => $inventoryItems->lastItem(),

                'total' => $inventoryItems->total(),

                'links' => $inventoryItems
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
                    ->values()
                    ->all(),
            ],
        ],

        'filters' => [
            'search' => (string) $request->query(
                'search',
                '',
            ),

            'warehouse_id' => (string) $request->query(
                'warehouse_id',
                '',
            ),

            'state' => (string) $request->query(
                'state',
                '',
            ),

            'sort' => (string) $request->query(
                'sort',
                'name',
            ),

            'per_page' => (string) $request->query(
                'per_page',
                '25',
            ),
        ],

        'options' => [
            'warehouses' => $warehouseOptions,
            'states' => $stateOptions,
            'sorts' => $sortOptions,
            'perPage' => $perPageOptions
        ],

        'createComponentUrl' => $createComponentUrl
    ],
);
        
    }
}