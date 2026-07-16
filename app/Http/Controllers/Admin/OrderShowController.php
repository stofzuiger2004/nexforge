<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderDetailResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderShowController extends Controller
{
    public function __invoke(
        Request $request,
        Order $order,
    ): Response {
        Gate::authorize(
            'view',
            $order,
        );

        $order->load([
            'user:id,name,email',

            'addresses',

            'items' => static fn ($query) => $query->orderBy(
                'line_number',
            ),

            'items.sourceConfiguration:id,public_id',

            'items.components' => static fn ($query) => $query->orderBy(
                'line_number',
            ),

            'adjustments' => static fn ($query) => $query->orderBy(
                'line_number',
            ),

            'statusHistory' => static fn ($query) => $query
                ->with(
                    'actor:id,name,email',
                )
                ->latest('id'),

            'payments' => static fn ($query) => $query
                ->withCount(
                    'webhookEvents',
                )
                ->with([
                    'refunds' => static fn ($query) => $query->latest(
                        'id',
                    ),

                    'chargebacks' => static fn ($query) => $query->latest(
                        'id',
                    ),
                ])
                ->orderByDesc(
                    'attempt_number',
                ),

            'inventoryReservations' => static fn ($query) => $query
                ->with([
                    'warehouse:id,code,name',

                    'items' => static fn ($query) => $query->orderBy(
                        'id',
                    ),
                ])
                ->latest('id'),
        ]);

        return Inertia::render(
            'admin/orders/show',
            [
                'order' => (
                    new AdminOrderDetailResource(
                        $order,
                    )
                )->resolve($request),
            ],
        );
    }
}
