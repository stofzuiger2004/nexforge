import { Link } from '@inertiajs/react';
import {
    CircleAlert,
    Package,
} from 'lucide-react';

import { StatusBadge } from '@/components/admin/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/date';
import { formatMoney } from '@/lib/money';
import type { AdminOrderListItem } from '@/types/admin';

type OrdersTableProps = {
    orders: AdminOrderListItem[];
    emptyMessage?: string;
};

export function OrdersTable({
    orders,
    emptyMessage =
        'No orders match the current filters.',
}: OrdersTableProps) {
    if (orders.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed px-6 py-16 text-center">
                <Package className="mx-auto size-8 text-muted-foreground" />

                <h3 className="mt-4 font-semibold">
                    No orders found
                </h3>

                <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                    {emptyMessage}
                </p>
            </div>
        );
    }

    return (
        <>
            <div className="hidden overflow-hidden rounded-2xl border bg-background md:block">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                Order
                            </TableHead>

                            <TableHead>
                                Customer
                            </TableHead>

                            <TableHead>
                                Order status
                            </TableHead>

                            <TableHead>
                                Payment
                            </TableHead>

                            <TableHead>
                                Fulfilment
                            </TableHead>

                            <TableHead className="text-right">
                                Total
                            </TableHead>

                            <TableHead className="text-right">
                                Placed
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {orders.map((order) => (
                            <TableRow
                                key={
                                    order.public_id
                                }
                            >
                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        {order.requires_attention && (
                                            <CircleAlert className="size-4 shrink-0 text-destructive" />
                                        )}

                                        <div>
                                            <Link
                                                href={
                                                    order.href
                                                }
                                                className="font-medium hover:underline"
                                            >
                                                {
                                                    order.order_number
                                                }
                                            </Link>

                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {
                                                    order.item_count
                                                }{' '}
                                                item
                                                {order.item_count
                                                    === 1
                                                    ? ''
                                                    : 's'}
                                            </p>
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <p className="font-medium">
                                        {
                                            order
                                                .customer
                                                .name
                                        }
                                    </p>

                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {
                                            order
                                                .customer
                                                .email
                                        }
                                    </p>
                                </TableCell>

                                <TableCell>
                                    <StatusBadge
                                        status={
                                            order.status
                                        }
                                    />
                                </TableCell>

                                <TableCell>
                                    <StatusBadge
                                        status={
                                            order.payment_status
                                        }
                                    />
                                </TableCell>

                                <TableCell>
                                    <StatusBadge
                                        status={
                                            order.fulfillment_status
                                        }
                                    />
                                </TableCell>

                                <TableCell className="text-right font-medium">
                                    {formatMoney(
                                        order.total_in_cents,
                                        order.currency,
                                    )}
                                </TableCell>

                                <TableCell className="text-right text-sm text-muted-foreground">
                                    {formatDateTime(
                                        order.placed_at,
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="space-y-3 md:hidden">
                {orders.map((order) => (
                    <Link
                        key={order.public_id}
                        href={order.href}
                        className="block rounded-2xl border bg-background p-4"
                    >
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <div className="flex items-center gap-2">
                                    {order.requires_attention && (
                                        <CircleAlert className="size-4 text-destructive" />
                                    )}

                                    <p className="font-medium">
                                        {
                                            order.order_number
                                        }
                                    </p>
                                </div>

                                <p className="mt-2 text-sm text-muted-foreground">
                                    {
                                        order.customer
                                            .name
                                    }
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    {
                                        order.customer
                                            .email
                                    }
                                </p>
                            </div>

                            <p className="font-semibold">
                                {formatMoney(
                                    order.total_in_cents,
                                    order.currency,
                                )}
                            </p>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            <StatusBadge
                                status={
                                    order.status
                                }
                            />

                            <StatusBadge
                                status={
                                    order.payment_status
                                }
                            />

                            <StatusBadge
                                status={
                                    order.fulfillment_status
                                }
                            />
                        </div>
                    </Link>
                ))}
            </div>
        </>
    );
}