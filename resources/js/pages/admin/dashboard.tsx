import { Head, Link } from '@inertiajs/react';
import {
    CircleAlert,
    Clock3,
    PackageCheck,
    ReceiptText,
    ShoppingBag,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { OrdersTable } from '@/components/admin/orders-table';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/admin-layout';
import { formatMoney } from '@/lib/money';
import type { AdminOrderListItem } from '@/types/admin';

type DashboardProps = {
    can: {
        view_payments: boolean;
    };
    metrics: {
        total_orders: number;
        orders_today: number;
        pending_payment: number;
        fulfillment_queue: number;
        manual_review: number;

        revenue_by_currency: {
            currency: string;
            paid_in_cents: number;
            refunded_in_cents: number;
            charged_back_in_cents: number;
            net_in_cents: number;
        }[];
    };

    statusBreakdown: {
        status: string;
        label: string;
        count: number;
    }[];

    recentOrders: AdminOrderListItem[];
    attentionOrders: AdminOrderListItem[];
};

export default function Dashboard({
    can,
    metrics,
    statusBreakdown,
    recentOrders,
    attentionOrders,
}: DashboardProps) {
    return (
        <AdminLayout
            title="Dashboard"
            description="Orders, payments, and fulfilment activity across the store."
            actions={
                <Button asChild>
                    <Link href="/admin/orders">View all orders</Link>
                </Button>
            }
        >
            <Head title="Admin dashboard" />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <MetricCard
                    icon={ShoppingBag}
                    label="Total orders"
                    value={metrics.total_orders}
                />

                <MetricCard
                    icon={ReceiptText}
                    label="Orders today"
                    value={metrics.orders_today}
                />

                <MetricCard
                    icon={Clock3}
                    label="Awaiting payment"
                    value={metrics.pending_payment}
                />

                <MetricCard
                    icon={PackageCheck}
                    label="Fulfilment queue"
                    value={metrics.fulfillment_queue}
                />

                <MetricCard
                    icon={CircleAlert}
                    label="Manual review"
                    value={metrics.manual_review}
                    attention={metrics.manual_review > 0}
                />
            </div>
            {can.view_payments && (
            <div className="mt-8 grid gap-6 xl:grid-cols-[1fr_24rem]">
                <section className="rounded-2xl border bg-background p-5 sm:p-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="font-semibold">
                                Net collected revenue
                            </h2>

                            <p className="mt-1 text-sm text-muted-foreground">
                                Paid amounts less refunds and chargebacks.
                            </p>
                        </div>
                    </div>

                    <div className="mt-6 space-y-5">
                        {metrics.revenue_by_currency.length > 0 ? (
                            metrics.revenue_by_currency.map((revenue) => (
                                <div
                                    key={revenue.currency}
                                    className="flex items-end justify-between border-b pb-5 last:border-0 last:pb-0"
                                >
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            {revenue.currency}
                                        </p>

                                        <p className="mt-1 text-3xl font-semibold">
                                            {formatMoney(
                                                revenue.net_in_cents,
                                                revenue.currency,
                                            )}
                                        </p>
                                    </div>

                                    <div className="text-right text-xs text-muted-foreground">
                                        <p>
                                            Paid{' '}
                                            {formatMoney(
                                                revenue.paid_in_cents,
                                                revenue.currency,
                                            )}
                                        </p>

                                        <p className="mt-1">
                                            Refunded{' '}
                                            {formatMoney(
                                                revenue.refunded_in_cents,
                                                revenue.currency,
                                            )}
                                        </p>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No completed payments yet.
                            </p>
                        )}
                    </div>
                </section>

                <section className="rounded-2xl border bg-background p-5 sm:p-6">
                    <h2 className="font-semibold">Order status</h2>

                    <div className="mt-6 space-y-4">
                        {statusBreakdown.map((item) => {
                            const percentage =
                                metrics.total_orders > 0
                                    ? Math.round(
                                          (item.count / metrics.total_orders) *
                                              100,
                                      )
                                    : 0;

                            return (
                                <div key={item.status}>
                                    <div className="flex justify-between gap-4 text-sm">
                                        <span className="text-muted-foreground">
                                            {item.label}
                                        </span>

                                        <span className="font-medium">
                                            {item.count}
                                        </span>
                                    </div>

                                    <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-foreground"
                                            style={{
                                                width: `${percentage}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>
            </div>
            )}

            {attentionOrders.length > 0 && (
                <section className="mt-8">
                    <div className="mb-5">
                        <h2 className="text-xl font-semibold">
                            Requires attention
                        </h2>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Failed payments, manual reviews, stale checkouts, or
                            paid orders without fulfilment.
                        </p>
                    </div>

                    <OrdersTable orders={attentionOrders} />
                </section>
            )}

            <section className="mt-8">
                <div className="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-semibold">Recent orders</h2>

                        <p className="mt-1 text-sm text-muted-foreground">
                            The latest orders created in the store.
                        </p>
                    </div>

                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/orders">View all</Link>
                    </Button>
                </div>

                <OrdersTable
                    orders={recentOrders}
                    emptyMessage="Orders will appear here after customers complete the review step."
                />
            </section>
        </AdminLayout>
    );
}

type MetricCardProps = {
    icon: LucideIcon;
    label: string;
    value: number;
    attention?: boolean;
};

function MetricCard({
    icon: Icon,
    label,
    value,
    attention = false,
}: MetricCardProps) {
    return (
        <article className="rounded-2xl border bg-background p-5">
            <div className="flex items-center justify-between">
                <span className="grid size-9 place-items-center rounded-xl bg-muted">
                    <Icon className="size-4" />
                </span>

                {attention && (
                    <span className="size-2 rounded-full bg-destructive" />
                )}
            </div>

            <p className="mt-5 text-3xl font-semibold">{value}</p>

            <p className="mt-1 text-sm text-muted-foreground">{label}</p>
        </article>
    );
}
