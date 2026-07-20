import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminInventoryDetail,
    AdminInventoryDetailPrice,
    AdminPriceHistoryItem,
    AdminSharedProps,
} from '@/types/admin';

import { ImageIcon } from 'lucide-react';

type AdjustmentOption = {
    value: string;
    label: string;
};

type InventoryShowProps = {
    inventoryItem: AdminInventoryDetail;
    priceHistory: AdminPriceHistoryItem[];
    adjustmentOptions: AdjustmentOption[];
};

function moneyInputValue(amountInCents: number | null): string {
    if (amountInCents === null) {
        return '';
    }

    return `${Math.floor(amountInCents / 100)}.${String(
        amountInCents % 100,
    ).padStart(2, '0')}`;
}

function formatMoney(amountInCents: number, currency: string): string {
    return new Intl.NumberFormat('en-BE', {
        style: 'currency',
        currency,
    }).format(amountInCents / 100);
}

function formatDate(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-BE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function signedQuantity(value: number): string {
    return value > 0 ? `+${value}` : `${value}`;
}

function PriceEditor({ price }: { price: AdminInventoryDetailPrice }) {
    const form = useForm({
        amount: moneyInputValue(price.amount_in_cents),

        compare_at_amount: moneyInputValue(price.compare_at_amount_in_cents),

        reason: '',

        expected_lock_version: price.lock_version,
    });

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        form.patch(price.update_url, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    return (
        <form onSubmit={submit} className="rounded-lg border p-4">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <p className="font-medium">{price.price_list.name}</p>

                    <p className="text-xs text-muted-foreground">
                        {price.price_list.currency}
                        {price.price_list.is_default ? ' · Default' : ''}
                        {!price.price_list.is_active ? ' · Inactive' : ''}
                    </p>
                </div>

                <p className="font-semibold">
                    {formatMoney(
                        price.amount_in_cents,
                        price.price_list.currency,
                    )}
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label
                        htmlFor={`price-${price.id}`}
                        className="mb-1 block text-sm font-medium"
                    >
                        Selling price
                    </label>

                    <Input
                        id={`price-${price.id}`}
                        inputMode="decimal"
                        value={form.data.amount}
                        onChange={(event) =>
                            form.setData('amount', event.target.value)
                        }
                    />

                    {form.errors.amount && (
                        <p className="mt-1 text-sm text-destructive">
                            {form.errors.amount}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        htmlFor={`compare-${price.id}`}
                        className="mb-1 block text-sm font-medium"
                    >
                        Compare-at price
                    </label>

                    <Input
                        id={`compare-${price.id}`}
                        inputMode="decimal"
                        value={form.data.compare_at_amount}
                        onChange={(event) =>
                            form.setData(
                                'compare_at_amount',
                                event.target.value,
                            )
                        }
                        placeholder="Optional"
                    />
                </div>
            </div>

            <div className="mt-4">
                <label
                    htmlFor={`reason-${price.id}`}
                    className="mb-1 block text-sm font-medium"
                >
                    Reason
                </label>

                <Input
                    id={`reason-${price.id}`}
                    value={form.data.reason}
                    onChange={(event) =>
                        form.setData('reason', event.target.value)
                    }
                    placeholder="Supplier cost change, campaign, correction…"
                />

                {form.errors.reason && (
                    <p className="mt-1 text-sm text-destructive">
                        {form.errors.reason}
                    </p>
                )}
                {form.errors.expected_lock_version && (
                    <p className="mt-3 text-sm text-destructive">
                        {form.errors.expected_lock_version}
                    </p>
                )}
            </div>

            <Button type="submit" className="mt-4" disabled={form.processing}>
                {form.processing ? 'Saving…' : 'Update price'}
            </Button>
        </form>
    );
}

export default function InventoryShow({
    inventoryItem,
    priceHistory,
    adjustmentOptions,
}: InventoryShowProps) {
    const page = usePage<AdminSharedProps>();

    const adjustmentForm = useForm({
        type: 'receipt',
        quantity: '1',
        reason: '',
        reference: '',
        expected_lock_version: inventoryItem.stock.lock_version,
        idempotency_key: inventoryItem.form_tokens.adjustment,
    });

    const settingsForm = useForm({
        bin_location: inventoryItem.bin_location ?? '',
        safety_stock: inventoryItem.stock.safety_stock.toString(),
        reorder_point: inventoryItem.stock.reorder_point?.toString() ?? '',
        is_active: inventoryItem.stock.is_active,
        expected_lock_version: inventoryItem.stock.lock_version,
    });

    const submitAdjustment = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        adjustmentForm.post(inventoryItem.adjustment_url, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    const submitSettings = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        settingsForm.patch(inventoryItem.update_url, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    return (
        <AdminLayout
            title={inventoryItem.product.name}
            description={`${inventoryItem.variant.sku} · ${inventoryItem.warehouse.name}`}
            actions={
                <Button asChild variant="outline">
                    <Link href="/admin/inventory">Back to inventory</Link>
                </Button>
            }
        >
            <Head title={`Inventory · ${inventoryItem.variant.sku}`} />

            {page.props.flash.success && (
                <div className="mb-6 rounded-lg border bg-background px-4 py-3 text-sm">
                    {page.props.flash.success}
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    ['On hand', inventoryItem.stock.quantity_on_hand],
                    ['Reserved', inventoryItem.stock.quantity_reserved],
                    ['Available', inventoryItem.stock.available],
                    ['Reservable', inventoryItem.stock.reservable],
                ].map(([label, value]) => (
                    <div
                        key={label}
                        className="rounded-xl border bg-background p-5"
                    >
                        <p className="text-sm text-muted-foreground">{label}</p>

                        <p className="mt-2 text-3xl font-semibold tabular-nums">
                            {value}
                        </p>
                    </div>
                ))}
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
                <section className="rounded-xl border bg-background p-5">
                    <h2 className="text-lg font-semibold">
                        Inventory information
                    </h2>

                    <dl className="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground">Product</dt>
                            <dd className="mt-1 font-medium">
                                {inventoryItem.product.name}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-muted-foreground">Variant</dt>
                            <dd className="mt-1 font-medium">
                                {inventoryItem.variant.name}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-muted-foreground">SKU</dt>
                            <dd className="mt-1 font-mono">
                                {inventoryItem.variant.sku}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-muted-foreground">Warehouse</dt>
                            <dd className="mt-1 font-medium">
                                {inventoryItem.warehouse.name}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-muted-foreground">Bin</dt>
                            <dd className="mt-1">
                                {inventoryItem.bin_location ?? '—'}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-muted-foreground">
                                Last counted
                            </dt>
                            <dd className="mt-1">
                                {formatDate(inventoryItem.last_counted_at)}
                            </dd>
                        </div>
                        {inventoryItem.image ? (
                            <div className="overflow-hidden rounded-xl border bg-muted/30">
                                <div className="aspect-square">
                                    <img
                                        src={inventoryItem.image.url}
                                        alt={inventoryItem.image.alt}
                                        className="h-full w-full object-contain p-6"
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="flex aspect-square items-center justify-center rounded-xl border bg-muted/30 text-sm text-muted-foreground">
                                No image available
                            </div>
                        )}
                    </dl>
                </section>

                {inventoryItem.can.adjust_inventory && (
                    <section className="rounded-xl border bg-background p-5">
                        <h2 className="text-lg font-semibold">Adjust stock</h2>

                        <form
                            onSubmit={submitAdjustment}
                            className="mt-4 space-y-4"
                        >
                            <div>
                                <label
                                    htmlFor="adjustment-type"
                                    className="mb-1 block text-sm font-medium"
                                >
                                    Adjustment type
                                </label>

                                <select
                                    id="adjustment-type"
                                    value={adjustmentForm.data.type}
                                    onChange={(event) =>
                                        adjustmentForm.setData(
                                            'type',
                                            event.target.value,
                                        )
                                    }
                                    className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                >
                                    {adjustmentOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label
                                    htmlFor="quantity"
                                    className="mb-1 block text-sm font-medium"
                                >
                                    {adjustmentForm.data.type === 'stock_count'
                                        ? 'Counted quantity'
                                        : 'Quantity'}
                                </label>

                                <Input
                                    id="quantity"
                                    type="number"
                                    min="0"
                                    value={adjustmentForm.data.quantity}
                                    onChange={(event) =>
                                        adjustmentForm.setData(
                                            'quantity',
                                            event.target.value,
                                        )
                                    }
                                />

                                {adjustmentForm.errors.quantity && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {adjustmentForm.errors.quantity}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="adjustment-reason"
                                    className="mb-1 block text-sm font-medium"
                                >
                                    Reason
                                </label>

                                <Input
                                    id="adjustment-reason"
                                    value={adjustmentForm.data.reason}
                                    onChange={(event) =>
                                        adjustmentForm.setData(
                                            'reason',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Supplier delivery, recount, damaged item…"
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="reference"
                                    className="mb-1 block text-sm font-medium"
                                >
                                    Reference
                                </label>

                                <Input
                                    id="reference"
                                    value={adjustmentForm.data.reference}
                                    onChange={(event) =>
                                        adjustmentForm.setData(
                                            'reference',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Optional PO, invoice, or ticket"
                                />
                            </div>
                            {adjustmentForm.errors.expected_lock_version && (
                                <p className="text-sm text-destructive">
                                    {
                                        adjustmentForm.errors
                                            .expected_lock_version
                                    }
                                </p>
                            )}

                            <Button
                                type="submit"
                                disabled={adjustmentForm.processing}
                            >
                                {adjustmentForm.processing
                                    ? 'Applying…'
                                    : 'Apply adjustment'}
                            </Button>
                        </form>
                    </section>
                )}
            </div>

            {inventoryItem.can.manage_inventory && (
                <section className="mt-6 rounded-xl border bg-background p-5">
                    <h2 className="text-lg font-semibold">
                        Inventory settings
                    </h2>

                    <form
                        onSubmit={submitSettings}
                        className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                    >
                        <div>
                            <label
                                htmlFor="bin-location"
                                className="mb-1 block text-sm font-medium"
                            >
                                Bin location
                            </label>

                            <Input
                                id="bin-location"
                                value={settingsForm.data.bin_location}
                                onChange={(event) =>
                                    settingsForm.setData(
                                        'bin_location',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>

                        <div>
                            <label
                                htmlFor="safety-stock"
                                className="mb-1 block text-sm font-medium"
                            >
                                Safety stock
                            </label>

                            <Input
                                id="safety-stock"
                                type="number"
                                min="0"
                                value={settingsForm.data.safety_stock}
                                onChange={(event) =>
                                    settingsForm.setData(
                                        'safety_stock',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>

                        <div>
                            <label
                                htmlFor="reorder-point"
                                className="mb-1 block text-sm font-medium"
                            >
                                Reorder point
                            </label>

                            <Input
                                id="reorder-point"
                                type="number"
                                min="0"
                                value={settingsForm.data.reorder_point}
                                onChange={(event) =>
                                    settingsForm.setData(
                                        'reorder_point',
                                        event.target.value,
                                    )
                                }
                                placeholder="Optional"
                            />
                        </div>

                        <label className="flex items-center gap-3 self-end rounded-md border px-3 py-2">
                            <input
                                type="checkbox"
                                checked={settingsForm.data.is_active}
                                onChange={(event) =>
                                    settingsForm.setData(
                                        'is_active',
                                        event.target.checked,
                                    )
                                }
                            />

                            <span className="text-sm font-medium">
                                Active inventory item
                            </span>
                        </label>

                        {settingsForm.errors.expected_lock_version && (
                            <p className="text-sm text-destructive md:col-span-2 xl:col-span-4">
                                {settingsForm.errors.expected_lock_version}
                            </p>
                        )}

                        <div className="md:col-span-2 xl:col-span-4">
                            <Button
                                type="submit"
                                disabled={settingsForm.processing}
                            >
                                {settingsForm.processing
                                    ? 'Saving…'
                                    : 'Save settings'}
                            </Button>
                        </div>
                    </form>
                </section>
            )}

            {inventoryItem.can.view_prices && inventoryItem.prices && (
                <section className="mt-6 rounded-xl border bg-background p-5">
                    <h2 className="text-lg font-semibold">Selling prices</h2>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Prices apply to this variant across all warehouses.
                    </p>

                    <div className="mt-4 grid gap-4 xl:grid-cols-2">
                        {inventoryItem.prices.map((price) =>
                            inventoryItem.can.manage_prices ? (
                                <PriceEditor key={price.id} price={price} />
                            ) : (
                                <div
                                    key={price.id}
                                    className="rounded-lg border p-4"
                                >
                                    <p className="font-medium">
                                        {price.price_list.name}
                                    </p>

                                    <p className="mt-2 text-xl font-semibold">
                                        {formatMoney(
                                            price.amount_in_cents,
                                            price.price_list.currency,
                                        )}
                                    </p>
                                </div>
                            ),
                        )}
                    </div>
                </section>
            )}

            <section className="mt-6 rounded-xl border bg-background p-5">
                <h2 className="text-lg font-semibold">Active reservations</h2>

                <div className="mt-4 overflow-x-auto">
                    <table className="w-full min-w-[680px] text-sm">
                        <thead className="border-b text-left text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="py-3">Reservation</th>
                                <th className="py-3">Status</th>
                                <th className="py-3 text-right">Quantity</th>
                                <th className="py-3">Order</th>
                                <th className="py-3">Expires</th>
                            </tr>
                        </thead>

                        <tbody>
                            {inventoryItem.reservations.map((reservation) => (
                                <tr
                                    key={reservation.public_id}
                                    className="border-b last:border-b-0"
                                >
                                    <td className="py-3 font-mono text-xs">
                                        {reservation.public_id}
                                    </td>
                                    <td className="py-3">
                                        {reservation.status}
                                    </td>
                                    <td className="py-3 text-right">
                                        {reservation.outstanding_quantity}
                                    </td>
                                    <td className="py-3">
                                        {reservation.order?.order_number ?? '—'}
                                    </td>
                                    <td className="py-3">
                                        {formatDate(reservation.expires_at)}
                                    </td>
                                </tr>
                            ))}

                            {inventoryItem.reservations.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No active reservations.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="mt-6 rounded-xl border bg-background p-5">
                <h2 className="text-lg font-semibold">
                    Inventory movement history
                </h2>

                <div className="mt-4 space-y-3">
                    {inventoryItem.movements.map((movement) => (
                        <div
                            key={movement.public_id}
                            className="flex flex-col gap-2 rounded-lg border p-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <p className="font-medium">{movement.type}</p>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    {movement.reason ?? 'No reason recorded'}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    {movement.actor?.name ?? 'System'} ·{' '}
                                    {formatDate(movement.occurred_at)}
                                </p>
                            </div>

                            <div className="text-right text-sm tabular-nums">
                                <p>
                                    On hand:{' '}
                                    {signedQuantity(movement.on_hand_delta)}
                                </p>

                                <p>
                                    Reserved:{' '}
                                    {signedQuantity(movement.reserved_delta)}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    After: {movement.on_hand_after} /{' '}
                                    {movement.reserved_after}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {inventoryItem.can.view_prices && (
                <section className="mt-6 rounded-xl border bg-background p-5">
                    <h2 className="text-lg font-semibold">Price history</h2>

                    <div className="mt-4 space-y-3">
                        {priceHistory.map((history) => (
                            <div
                                key={history.id}
                                className="rounded-lg border p-4"
                            >
                                <div className="flex flex-col gap-2 sm:flex-row sm:justify-between">
                                    <div>
                                        <p className="font-medium">
                                            {history.price_list.name}
                                        </p>

                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {history.reason}
                                        </p>

                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {history.actor?.name ?? 'System'} ·{' '}
                                            {formatDate(history.changed_at)}
                                        </p>
                                    </div>

                                    <p className="font-medium">
                                        {formatMoney(
                                            history.old_amount_in_cents,
                                            history.price_list.currency,
                                        )}{' '}
                                        →{' '}
                                        {formatMoney(
                                            history.new_amount_in_cents,
                                            history.price_list.currency,
                                        )}
                                    </p>
                                </div>
                            </div>
                        ))}

                        {priceHistory.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No price changes have been recorded yet.
                            </p>
                        )}
                    </div>
                </section>
            )}
        </AdminLayout>
    );
}

function ProductImage({
    image,
    productName,
}: {
    image: {
        url: string;
        alt: string;
    } | null;
    productName: string;
}) {
    return (
        <div className="overflow-hidden rounded-xl border bg-muted/30">
            <div className="aspect-square">
                {image ? (
                    <img
                        src={image.url}
                        alt={image.alt}
                        className="h-full w-full object-contain p-6"
                        loading="eager"
                        decoding="async"
                    />
                ) : (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-3 text-muted-foreground">
                        <ImageIcon
                            className="size-10"
                            strokeWidth={1.5}
                        />

                        <div className="text-center">
                            <p className="text-sm font-medium">
                                No image available
                            </p>

                            <p className="mt-1 max-w-48 text-xs">
                                No product image has been
                                uploaded for {productName}.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
