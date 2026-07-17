import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CreditCard,
    MapPin,
    PackageCheck,
    ReceiptText,
    UserRound,
} from 'lucide-react';

import { StatusBadge, humanizeStatus } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/admin-layout';
import { formatDateTime } from '@/lib/date';
import { formatMoney, formatSignedMoney } from '@/lib/money';
import type {
    AdminInventoryReservation,
    AdminOrderAddress,
    AdminOrderDetail,
    AdminPayment,
} from '@/types/admin';

type OrderShowProps = {
    order: AdminOrderDetail;
};

export default function OrderShow({ order }: OrderShowProps) {
    return (
        <AdminLayout
            title={order.order_number}
            description={`Placed ${formatDateTime(
                order.dates.placed_at,
            )} by ${order.customer.name}.`}
            actions={
                <Button variant="outline" asChild>
                    <Link href="/admin/orders">
                        <ArrowLeft className="size-4" />
                        Orders
                    </Link>
                </Button>
            }
        >
            <Head title={`Order ${order.order_number}`} />

            <div className="flex flex-wrap gap-2">
                <StatusBadge status={order.status} />

                <StatusBadge status={order.payment_status} />

                <StatusBadge status={order.fulfillment_status} />
            </div>

            <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SummaryCard
                    icon={ReceiptText}
                    label="Order total"
                    value={formatMoney(
                        order.pricing.total_in_cents,
                        order.pricing.currency,
                    )}
                />

                <SummaryCard
                    icon={CreditCard}
                    label="Paid"
                    value={formatMoney(
                        order.pricing.paid_in_cents,
                        order.pricing.currency,
                    )}
                />

                <SummaryCard
                    icon={PackageCheck}
                    label="Fulfilment"
                    value={humanizeStatus(order.fulfillment_status)}
                />

                <SummaryCard
                    icon={UserRound}
                    label="Customer"
                    value={order.customer.name}
                />
            </div>

            <Tabs defaultValue="overview" className="mt-8">
                <TabsList className="h-auto flex-wrap">
                    <TabsTrigger value="overview">Overview</TabsTrigger>

                    <TabsTrigger value="payments">Payments</TabsTrigger>

                    <TabsTrigger value="inventory">Inventory</TabsTrigger>

                    <TabsTrigger value="timeline">Timeline</TabsTrigger>
                </TabsList>

                <TabsContent value="overview" className="mt-6">
                    <OverviewTab order={order} />
                </TabsContent>

                <TabsContent value="payments" className="mt-6">
                    <PaymentsTab order={order} />
                </TabsContent>

                <TabsContent value="inventory" className="mt-6">
                    <InventoryTab order={order} />
                </TabsContent>

                <TabsContent value="timeline" className="mt-6">
                    <TimelineTab order={order} />
                </TabsContent>
            </Tabs>
        </AdminLayout>
    );
}

function OverviewTab({ order }: OrderShowProps) {
    return (
        <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div className="space-y-6">
                {order.items.map((item) => (
                    <section
                        key={item.id}
                        className="overflow-hidden rounded-2xl border bg-background"
                    >
                        <div className="p-5 sm:p-6">
                            <p className="text-xs tracking-[0.12em] text-muted-foreground uppercase">
                                Line {item.line_number}
                            </p>

                            <div className="mt-2 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                                <div>
                                    <h2 className="text-lg font-semibold">
                                        {item.name}
                                    </h2>

                                    {item.sku && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            SKU {item.sku}
                                        </p>
                                    )}
                                </div>

                                <p className="font-semibold">
                                    {formatMoney(
                                        item.line_total_in_cents,
                                        order.pricing.currency,
                                    )}
                                </p>
                            </div>
                        </div>

                        {item.components.length > 0 && (
                            <div className="divide-y border-t">
                                {item.components.map((component) => (
                                    <div
                                        key={component.id}
                                        className="flex items-start justify-between gap-5 px-5 py-4 sm:px-6"
                                    >
                                        <div>
                                            <p className="text-xs text-muted-foreground">
                                                {component.slot_label}
                                            </p>

                                            <p className="mt-1 text-sm font-medium">
                                                {component.name}
                                            </p>

                                            <p className="mt-1 text-xs text-muted-foreground">
                                                SKU {component.sku}
                                            </p>
                                        </div>

                                        <div className="text-right">
                                            <p className="text-sm font-medium">
                                                {component.line_total_in_cents !==
                                                null
                                                    ? formatMoney(
                                                          component.line_total_in_cents,
                                                          order.pricing
                                                              .currency,
                                                      )
                                                    : '—'}
                                            </p>

                                            {component.quantity > 1 && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Qty {component.quantity}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </section>
                ))}
            </div>

            <div className="space-y-6">
                <section className="rounded-2xl border bg-background p-5">
                    <h2 className="font-semibold">Customer</h2>

                    <DefinitionList
                        rows={[
                            ['Name', order.customer.name],
                            ['Email', order.customer.email],
                            [
                                'Account',
                                order.customer.has_account
                                    ? 'Registered customer'
                                    : 'Guest customer',
                            ],
                            ['Locale', order.customer.locale],
                        ]}
                    />
                </section>

                {order.addresses && (
                    <section className="space-y-4">
                        {order.addresses.map((address) => (
                            <AddressCard key={address.id} address={address} />
                        ))}
                    </section>
                )}

                <PricingCard order={order} />

                <section className="rounded-2xl border bg-background p-5">
                    <h2 className="font-semibold">References</h2>

                    <DefinitionList
                        rows={[
                            [
                                'Configuration',
                                order.references.configuration_public_id,
                            ],
                            [
                                'Configuration version',
                                order.references.configuration_version,
                            ],
                            [
                                'Reservation',
                                order.references.reservation_public_id,
                            ],
                            ['Terms version', order.references.terms_version],
                            [
                                'Terms accepted',
                                order.references.terms_accepted_at,
                            ],
                        ]}
                    />
                </section>
            </div>
        </div>
    );
}

function PaymentsTab({ order }: OrderShowProps) {
    if (!order.can.view_payments) {
        return (
            <PermissionNotice
                title="Payment access required"
                description="Your role does not permit access to payment attempts, refunds, or chargebacks."
            />
        );
    }

    if (!order.payments || order.payments.length === 0) {
        return (
            <EmptyPanel
                title="No payment attempts"
                description="Payment attempts will appear here once Mollie payment creation is implemented."
            />
        );
    }

    return (
        <div className="space-y-5">
            {order.payments.map((payment) => (
                <PaymentCard key={payment.public_id} payment={payment} />
            ))}
        </div>
    );
}

function PaymentCard({ payment }: { payment: AdminPayment }) {
    return (
        <section className="rounded-2xl border bg-background p-5 sm:p-6">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="font-semibold">
                            Payment attempt {payment.attempt_number}
                        </h2>

                        <StatusBadge status={payment.status} />
                    </div>

                    <p className="mt-2 text-sm text-muted-foreground">
                        {payment.provider}
                        {payment.provider_payment_id
                            ? ` · ${payment.provider_payment_id}`
                            : ''}
                    </p>
                </div>

                <p className="text-xl font-semibold">
                    {formatMoney(payment.amount_in_cents, payment.currency)}
                </p>
            </div>

            <div className="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Definition
                    label="Provider status"
                    value={payment.provider_status}
                />

                <Definition label="Method" value={payment.method} />

                <Definition label="Mode" value={payment.mode} />

                <Definition
                    label="Webhook events"
                    value={payment.webhook_event_count}
                />

                <Definition
                    label="Created"
                    value={formatDateTime(payment.dates.created_at)}
                />

                <Definition
                    label="Paid"
                    value={formatDateTime(payment.dates.paid_at)}
                />

                <Definition
                    label="Last synchronized"
                    value={formatDateTime(payment.dates.last_synced_at)}
                />
            </div>

            {payment.failure_message && (
                <div className="mt-6 rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm">
                    <p className="font-medium text-destructive">
                        Payment failure
                    </p>

                    <p className="mt-2 text-muted-foreground">
                        {payment.failure_message}
                    </p>
                </div>
            )}

            {payment.refunds.length > 0 && (
                <div className="mt-6 border-t pt-6">
                    <h3 className="font-medium">Refunds</h3>

                    <div className="mt-4 space-y-3">
                        {payment.refunds.map((refund) => (
                            <div
                                key={refund.public_id}
                                className="flex justify-between gap-4 rounded-xl bg-muted/30 p-4"
                            >
                                <div>
                                    <StatusBadge status={refund.status} />

                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {refund.provider_refund_id ??
                                            refund.public_id}
                                    </p>
                                </div>

                                <p className="font-medium">
                                    {formatMoney(
                                        refund.amount_in_cents,
                                        refund.currency,
                                    )}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {payment.chargebacks.length > 0 && (
                <div className="mt-6 border-t pt-6">
                    <h3 className="font-medium">Chargebacks</h3>

                    <div className="mt-4 space-y-3">
                        {payment.chargebacks.map((chargeback) => (
                            <div
                                key={chargeback.public_id}
                                className="flex justify-between gap-4 rounded-xl border border-destructive/30 bg-destructive/5 p-4"
                            >
                                <div>
                                    <StatusBadge status={chargeback.status} />

                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {chargeback.provider_chargeback_id}
                                    </p>
                                </div>

                                <p className="font-medium">
                                    {formatMoney(
                                        chargeback.amount_in_cents,
                                        chargeback.currency,
                                    )}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}

function InventoryTab({ order }: OrderShowProps) {
    if (!order.can.view_inventory) {
        return (
            <PermissionNotice
                title="Inventory access required"
                description="Your role does not permit access to stock reservations or allocation details."
            />
        );
    }

    if (
        !order.inventory_reservations ||
        order.inventory_reservations.length === 0
    ) {
        return (
            <EmptyPanel
                title="No inventory reservations"
                description="This order is not currently linked to an inventory reservation."
            />
        );
    }

    return (
        <div className="space-y-5">
            {order.inventory_reservations.map((reservation) => (
                <ReservationCard
                    key={reservation.public_id}
                    reservation={reservation}
                />
            ))}
        </div>
    );
}

function ReservationCard({
    reservation,
}: {
    reservation: AdminInventoryReservation;
}) {
    return (
        <section className="overflow-hidden rounded-2xl border bg-background">
            <div className="p-5 sm:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="font-semibold">
                                Inventory reservation
                            </h2>

                            <StatusBadge status={reservation.status} />
                        </div>

                        <p className="mt-2 font-mono text-xs text-muted-foreground">
                            {reservation.public_id}
                        </p>
                    </div>

                    <div className="text-sm sm:text-right">
                        <p className="font-medium">
                            {reservation.warehouse.name}
                        </p>

                        <p className="mt-1 text-xs text-muted-foreground">
                            {reservation.warehouse.code}
                        </p>
                    </div>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-3">
                    <Definition
                        label="Reserved"
                        value={formatDateTime(reservation.reserved_at)}
                    />

                    <Definition
                        label="Expires"
                        value={formatDateTime(reservation.expires_at)}
                    />

                    <Definition
                        label="Committed"
                        value={formatDateTime(reservation.committed_at)}
                    />
                </div>
            </div>

            <div className="divide-y border-t">
                {reservation.items.map((item) => (
                    <div
                        key={item.id}
                        className="flex items-start justify-between gap-5 px-5 py-4 sm:px-6"
                    >
                        <div>
                            <p className="font-medium">{item.name}</p>

                            <p className="mt-1 text-xs text-muted-foreground">
                                SKU {item.sku}
                            </p>
                        </div>

                        <div className="text-right text-sm">
                            <p className="font-medium">
                                Reserved {item.quantity}
                            </p>

                            <p className="mt-1 text-xs text-muted-foreground">
                                Outstanding {item.outstanding_quantity}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}

function TimelineTab({ order }: OrderShowProps) {
    if (order.timeline.length === 0) {
        return (
            <EmptyPanel
                title="No history recorded"
                description="Order status changes will appear here."
            />
        );
    }

    return (
        <section className="rounded-2xl border bg-background p-5 sm:p-6">
            <div className="space-y-0">
                {order.timeline.map((event, index) => (
                    <article
                        key={event.id}
                        className="relative grid grid-cols-[1.25rem_1fr] gap-4 pb-8 last:pb-0"
                    >
                        {index < order.timeline.length - 1 && (
                            <span className="absolute top-5 left-[0.59375rem] h-[calc(100%-0.5rem)] w-px bg-border" />
                        )}

                        <span className="relative mt-1 size-5 rounded-full border-4 border-background bg-foreground" />

                        <div>
                            <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                                <div>
                                    <p className="font-medium">
                                        {humanizeStatus(event.to_status)}
                                    </p>

                                    <p className="mt-1 text-xs tracking-wide text-muted-foreground uppercase">
                                        {event.category}
                                    </p>
                                </div>

                                <p className="text-xs text-muted-foreground">
                                    {formatDateTime(event.created_at)}
                                </p>
                            </div>

                            {event.reason && (
                                <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                    {event.reason}
                                </p>
                            )}

                            {event.actor && (
                                <p className="mt-2 text-xs text-muted-foreground">
                                    By {event.actor}
                                </p>
                            )}
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}

function AddressCard({ address }: { address: AdminOrderAddress }) {
    return (
        <section className="rounded-2xl border bg-background p-5">
            <div className="flex items-center gap-2">
                <MapPin className="size-4" />

                <h2 className="font-semibold">
                    {humanizeStatus(address.type)} address
                </h2>
            </div>

            <address className="mt-4 text-sm leading-6 text-muted-foreground not-italic">
                <p className="font-medium text-foreground">
                    {address.first_name} {address.last_name}
                </p>

                {address.company && <p>{address.company}</p>}

                <p>{address.address_line_1}</p>

                {address.address_line_2 && <p>{address.address_line_2}</p>}

                <p>
                    {address.postal_code} {address.city}
                </p>

                <p>{address.country_code}</p>

                <p className="mt-3">{address.email}</p>

                {address.phone && <p>{address.phone}</p>}
            </address>
        </section>
    );
}

function PricingCard({ order }: OrderShowProps) {
    const pricing = order.pricing;

    return (
        <section className="rounded-2xl border bg-background p-5">
            <h2 className="font-semibold">Pricing</h2>

            <div className="mt-5 space-y-3 text-sm">
                <PriceRow
                    label="Subtotal"
                    value={formatMoney(
                        pricing.subtotal_in_cents,
                        pricing.currency,
                    )}
                />

                <PriceRow
                    label="Adjustments"
                    value={formatSignedMoney(
                        pricing.adjustment_total_in_cents,
                        pricing.currency,
                    )}
                />

                <PriceRow
                    label="Shipping"
                    value={formatMoney(
                        pricing.shipping_in_cents,
                        pricing.currency,
                    )}
                />

                <PriceRow
                    label="Tax"
                    value={formatMoney(pricing.tax_in_cents, pricing.currency)}
                />

                <Separator />

                <PriceRow
                    label="Total"
                    value={formatMoney(
                        pricing.total_in_cents,
                        pricing.currency,
                    )}
                    strong
                />

                <PriceRow
                    label="Paid"
                    value={formatMoney(pricing.paid_in_cents, pricing.currency)}
                />

                <PriceRow
                    label="Outstanding"
                    value={formatMoney(
                        pricing.outstanding_in_cents,
                        pricing.currency,
                    )}
                />
            </div>
        </section>
    );
}

function SummaryCard({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof ReceiptText;
    label: string;
    value: string;
}) {
    return (
        <article className="rounded-2xl border bg-background p-5">
            <span className="grid size-9 place-items-center rounded-xl bg-muted">
                <Icon className="size-4" />
            </span>

            <p className="mt-4 text-xs text-muted-foreground">{label}</p>

            <p className="mt-1 truncate font-semibold">{value}</p>
        </article>
    );
}

function DefinitionList({ rows }: { rows: [string, unknown][] }) {
    return (
        <dl className="mt-5 space-y-4">
            {rows.map(([label, value]) => (
                <Definition key={label} label={label} value={value} />
            ))}
        </dl>
    );
}

function Definition({ label, value }: { label: string; value: unknown }) {
    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>

            <dd className="mt-1 text-sm font-medium break-words">
                {value === null || value === undefined || value === ''
                    ? '—'
                    : String(value)}
            </dd>
        </div>
    );
}

function PriceRow({
    label,
    value,
    strong = false,
}: {
    label: string;
    value: string;
    strong?: boolean;
}) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>

            <span className={strong ? 'font-semibold' : 'font-medium'}>
                {value}
            </span>
        </div>
    );
}

function PermissionNotice({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return (
        <div className="rounded-2xl border border-dashed bg-background px-6 py-14 text-center">
            <h2 className="font-semibold">{title}</h2>

            <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-muted-foreground">
                {description}
            </p>
        </div>
    );
}

function EmptyPanel({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return (
        <div className="rounded-2xl border border-dashed bg-background px-6 py-14 text-center">
            <h2 className="font-semibold">{title}</h2>

            <p className="mx-auto mt-2 max-w-lg text-sm text-muted-foreground">
                {description}
            </p>
        </div>
    );
}
