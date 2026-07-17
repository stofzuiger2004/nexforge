<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrderFiltersRequest;
use App\Http\Resources\Admin\AdminOrderListResource;
use App\Models\Order;
use App\Services\Admin\AdminOrderFilterOptions;
use App\Services\Admin\AdminOrderQueryService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderIndexController extends Controller
{
    public function __invoke(
        AdminOrderFiltersRequest $request,
        AdminOrderQueryService $orderQuery,
        AdminOrderFilterOptions $filterOptions,
    ): Response {
        Gate::authorize(
            'viewAny',
            Order::class,
        );

        $filters =
            $request->filters();

        $query = Order::query()
    ->with('user:id,name,email')
    ->withCount('items');

        $orders = $orderQuery
            ->apply(
                $query,
                $filters,
            )
            ->paginate(
                $filters['per_page'],
            )
            ->withQueryString();

        return Inertia::render(
            'admin/orders/index',
            [
                'orders' => [
                    'data' => $orders
                        ->getCollection()
                        ->map(
                            fn (
                                Order $order,
                            ): array => (
                                new AdminOrderListResource(
                                    $order,
                                )
                            )->resolve($request),
                        )
                        ->values()
                        ->all(),

                    'meta' => [
                        'current_page' => $orders
                            ->currentPage(),

                        'last_page' => $orders
                            ->lastPage(),

                        'per_page' => $orders
                            ->perPage(),

                        'from' => $orders
                            ->firstItem(),

                        'to' => $orders
                            ->lastItem(),

                        'total' => $orders
                            ->total(),
                    ],

                    'links' => [
                        'previous' => $orders
                            ->previousPageUrl(),

                        'next' => $orders
                            ->nextPageUrl(),
                    ],
                ],

                'filters' => $filters,

                'filterOptions' => $filterOptions->all(),
            ],
        );
    }
}
