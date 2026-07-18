<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderListResource;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('admin.access');
        $canViewPayments = Gate::allows('payments.view');

        $revenueByCurrency = $canViewPayments ? $this->revenueByCurrency() : [];

        $recentOrders = $this->baseOrderListQuery()->orderByDesc('placed_at')->limit(8)->get();

        $attentionOrders = $this
            ->baseOrderListQuery()
            ->where(
                function (
                    Builder $query,
                ): void {
                    $query
                        ->where(
                            'status',
                            OrderStatus::ManualReview
                                ->value,
                        )
                        ->orWhereIn(
                            'payment_status',
                            [
                                OrderPaymentStatus::Failed
                                    ->value,

                                OrderPaymentStatus::ChargedBack
                                    ->value,

                                OrderPaymentStatus::PartiallyChargedBack
                                    ->value,
                            ],
                        )
                        ->orWhere(
                            function (
                                Builder $query,
                            ): void {
                                $query
                                    ->where(
                                        'payment_status',
                                        OrderPaymentStatus::Paid
                                            ->value,
                                    )
                                    ->where(
                                        'fulfillment_status',
                                        OrderFulfillmentStatus::Unfulfilled
                                            ->value,
                                    );
                            },
                        )
                        ->orWhere(
                            function (
                                Builder $query,
                            ): void {
                                $query
                                    ->where(
                                        'status',
                                        OrderStatus::PendingPayment
                                            ->value,
                                    )
                                    ->where(
                                        'placed_at',
                                        '<',
                                        now()
                                            ->subHour(),
                                    );
                            },
                        );
                },
            )
            ->orderByDesc('placed_at')
            ->limit(8)
            ->get();

        return Inertia::render(
            'admin/dashboard',
            [
                'can' => [
                    'view_payments' => $canViewPayments
                ],
                'metrics' => [
                    'total_orders' => Order::query()->count(),

                    'orders_today' => Order::query()
                        ->whereBetween(
                            'created_at',
                            [
                                now()
                                    ->startOfDay(),

                                now()
                                    ->endOfDay(),
                            ],
                        )
                        ->count(),

                    'pending_payment' => Order::query()
                        ->where(
                            'status',
                            OrderStatus::PendingPayment
                                ->value,
                        )
                        ->count(),

                    'fulfillment_queue' => Order::query()
                        ->whereIn(
                            'fulfillment_status',
                            [
                                OrderFulfillmentStatus::StockAllocated
                                    ->value,

                                OrderFulfillmentStatus::Assembling
                                    ->value,

                                OrderFulfillmentStatus::ReadyToShip
                                    ->value,
                            ],
                        )
                        ->count(),

                    'manual_review' => Order::query()
                        ->where(
                            'status',
                            OrderStatus::ManualReview
                                ->value,
                        )
                        ->count(),

                    'revenue_by_currency' => $revenueByCurrency,
                ],

                'statusBreakdown' => $this->statusBreakdown(),

                'recentOrders' => $this->orderResources(
                    $recentOrders,
                    $request,
                ),

                'attentionOrders' => $this->orderResources(
                    $attentionOrders,
                    $request,
                ),
                
            ],
        );
    }
    /**
 * @return array<int, array{
 *     currency: string,
 *     paid_in_cents: int,
 *     refunded_in_cents: int,
 *     charged_back_in_cents: int,
 *     net_in_cents: int
 * }>
 */
private function revenueByCurrency(): array
{
    return Order::query()
        ->selectRaw(
            'currency,
             SUM(paid_in_cents) AS paid_total,
             SUM(refunded_in_cents) AS refunded_total,
             SUM(charged_back_in_cents) AS charged_back_total',
        )
        ->groupBy('currency')
        ->get()
        ->map(static function (Order $order): array {
            $paid = (int) ($order->paid_total ?? 0);
            $refunded = (int) ($order->refunded_total ?? 0);
            $chargedBack = (int) ($order->charged_back_total ?? 0);

            return [
                'currency' => $order->currency,
                'paid_in_cents' => $paid,
                'refunded_in_cents' => $refunded,
                'charged_back_in_cents' => $chargedBack,
                'net_in_cents' => max(
                    0,
                    $paid - $refunded - $chargedBack,
                ),
            ];
        })
        ->values()
        ->all();
}

    /**
     * @return Builder<Order>
     */
    private function baseOrderListQuery(): Builder
{
    return Order::query()
        ->with('user:id,name,email')
        ->withCount('items');
}

    /**
     * @return array<int, array<string, mixed>>
     */
    private function orderResources(
        $orders,
        Request $request,
    ): array {
        return $orders
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
            ->all();
    }

    /**
     * @return array<int, array{
     *     status: string,
     *     label: string,
     *     count: int
     * }>
     */
    private function statusBreakdown(): array
    {
        $counts = Order::query()
            ->selectRaw(
                'status, COUNT(*) AS aggregate',
            )
            ->groupBy('status')
            ->pluck(
                'aggregate',
                'status',
            );

        return collect(
            OrderStatus::cases(),
        )
            ->map(
                static fn (
                    OrderStatus $status,
                ): array => [
                    'status' => $status->value,

                    'label' => Str::headline(
                        $status->value,
                    ),

                    'count' => (int) (
                        $counts[
                            $status->value
                        ] ?? 0
                    ),
                ],
            )
            ->filter(
                static fn (
                    array $item,
                ): bool => $item['count'] > 0,
            )
            ->values()
            ->all();
    }
}
