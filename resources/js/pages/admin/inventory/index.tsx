import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminInventoryListItem,
    AdminPaginationData,
    AdminSharedProps,
} from '@/types/admin';

type FilterOption = {
    value: string | number;
    label: string;
};

type InventoryIndexProps = {
    inventoryItems: {
        data: AdminInventoryListItem[];
        pagination: AdminPaginationData;
    };

    filters: {
        search: string | null;
        warehouse_id: number | null;
        stock_state: string | null;
        sort: string;
        per_page: number;
    };

    filterOptions: {
        warehouses: FilterOption[];
        stock_states: FilterOption[];
        sorts: FilterOption[];
    };
};

function formatMoney(amountInCents: number, currency: string): string {
    return new Intl.NumberFormat('en-BE', {
        style: 'currency',
        currency,
    }).format(amountInCents / 100);
}

function stockStateLabel(
    state: AdminInventoryListItem['stock']['state'],
): string {
    return {
        in_stock: 'In stock',
        low_stock: 'Low stock',
        out_of_stock: 'Out of stock',
        inactive: 'Inactive',
    }[state];
}

function paginationLabel(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}

export default function InventoryIndex({
    inventoryItems,
    filters,
    filterOptions,
}: InventoryIndexProps) {
    const form = useForm({
        search: filters.search ?? '',
        warehouse_id: filters.warehouse_id?.toString() ?? '',
        stock_state: filters.stock_state ?? '',
        sort: filters.sort,
        per_page: filters.per_page.toString(),
    });

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        form.get('/admin/inventory', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const reset = (): void => {
        form.setData({
            search: '',
            warehouse_id: '',
            stock_state: '',
            sort: 'sku_asc',
            per_page: '25',
        });

        window.setTimeout(() => {
            form.get('/admin/inventory', {
                preserveScroll: true,
                preserveState: false,
                replace: true,
            });
        });
    };

    return (
        <AdminLayout
            title="Inventory"
            description="Manage warehouse stock, reservations, thresholds, and retail prices."
        >
            <Head title="Inventory" />

            <form
                onSubmit={submit}
                className="mb-6 grid gap-3 rounded-xl border bg-background p-4 md:grid-cols-2 xl:grid-cols-6"
            >
                <div className="xl:col-span-2">
                    <label
                        htmlFor="search"
                        className="mb-1 block text-sm font-medium"
                    >
                        Search
                    </label>

                    <Input
                        id="search"
                        value={form.data.search}
                        onChange={(event) =>
                            form.setData('search', event.target.value)
                        }
                        placeholder="Product, SKU, bin, warehouse…"
                    />
                </div>

                <div>
                    <label
                        htmlFor="warehouse"
                        className="mb-1 block text-sm font-medium"
                    >
                        Warehouse
                    </label>

                    <select
                        id="warehouse"
                        value={form.data.warehouse_id}
                        onChange={(event) =>
                            form.setData('warehouse_id', event.target.value)
                        }
                        className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">All warehouses</option>

                        {filterOptions.warehouses.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label
                        htmlFor="stock-state"
                        className="mb-1 block text-sm font-medium"
                    >
                        Stock status
                    </label>

                    <select
                        id="stock-state"
                        value={form.data.stock_state}
                        onChange={(event) =>
                            form.setData('stock_state', event.target.value)
                        }
                        className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="">All statuses</option>

                        {filterOptions.stock_states.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label
                        htmlFor="sort"
                        className="mb-1 block text-sm font-medium"
                    >
                        Sort
                    </label>

                    <select
                        id="sort"
                        value={form.data.sort}
                        onChange={(event) =>
                            form.setData('sort', event.target.value)
                        }
                        className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        {filterOptions.sorts.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label
                        htmlFor="per-page"
                        className="mb-1 block text-sm font-medium"
                    >
                        Per page
                    </label>

                    <select
                        id="per-page"
                        value={form.data.per_page}
                        onChange={(event) =>
                            form.setData('per_page', event.target.value)
                        }
                        className="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div className="flex gap-2 md:col-span-2 xl:col-span-6">
                    <Button type="submit" disabled={form.processing}>
                        Apply filters
                    </Button>

                    <Button type="button" variant="outline" onClick={reset}>
                        Reset
                    </Button>
                </div>
            </form>

            <div className="overflow-hidden rounded-xl border bg-background">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[960px] text-left text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-4 py-3">Product</th>
                                <th className="px-4 py-3">Warehouse</th>
                                <th className="px-4 py-3 text-right">
                                    On hand
                                </th>
                                <th className="px-4 py-3 text-right">
                                    Reserved
                                </th>
                                <th className="px-4 py-3 text-right">
                                    Available
                                </th>
                                <th className="px-4 py-3 text-right">
                                    Reservable
                                </th>
                                <th className="px-4 py-3">Price</th>
                                <th className="px-4 py-3">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            {inventoryItems.data.map((item) => (
                                <tr
                                    key={item.id}
                                    className="border-b last:border-b-0 hover:bg-muted/20"
                                >
                                    <td className="px-4 py-4">
                                        <Link
                                            href={item.href}
                                            className="font-medium hover:underline"
                                        >
                                            {item.product.name}
                                        </Link>

                                        <div className="mt-1 text-xs text-muted-foreground">
                                            {item.product.brand
                                                ? `${item.product.brand} · `
                                                : ''}
                                            {item.variant.sku}
                                        </div>
                                    </td>

                                    <td className="px-4 py-4">
                                        {item.warehouse.name}

                                        <div className="mt-1 text-xs text-muted-foreground">
                                            {item.bin_location ?? 'No bin'}
                                        </div>
                                    </td>

                                    <td className="px-4 py-4 text-right tabular-nums">
                                        {item.stock.quantity_on_hand}
                                    </td>

                                    <td className="px-4 py-4 text-right tabular-nums">
                                        {item.stock.quantity_reserved}
                                    </td>

                                    <td className="px-4 py-4 text-right font-medium tabular-nums">
                                        {item.stock.available}
                                    </td>

                                    <td className="px-4 py-4 text-right tabular-nums">
                                        {item.stock.reservable}
                                    </td>

                                    <td className="px-4 py-4">
                                        {item.price
                                            ? formatMoney(
                                                  item.price.amount_in_cents,
                                                  item.price.currency,
                                              )
                                            : '—'}
                                    </td>

                                    <td className="px-4 py-4">
                                        <span className="rounded-full border px-2 py-1 text-xs">
                                            {stockStateLabel(item.stock.state)}
                                        </span>
                                    </td>
                                </tr>
                            ))}

                            {inventoryItems.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-4 py-12 text-center text-muted-foreground"
                                    >
                                        No inventory items match these filters.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-muted-foreground">
                    Showing {inventoryItems.pagination.from ?? 0}–
                    {inventoryItems.pagination.to ?? 0} of{' '}
                    {inventoryItems.pagination.total}
                </p>

                <div className="flex flex-wrap gap-1">
                    {inventoryItems.pagination.links.map((link, index) =>
                        link.url ? (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url}
                                preserveScroll
                                className={[
                                    'rounded-md border px-3 py-1.5 text-sm',
                                    link.active
                                        ? 'bg-foreground text-background'
                                        : 'bg-background hover:bg-muted',
                                ].join(' ')}
                            >
                                {paginationLabel(link.label)}
                            </Link>
                        ) : (
                            <span
                                key={`${link.label}-${index}`}
                                className="rounded-md border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                            >
                                {paginationLabel(link.label)}
                            </span>
                        ),
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
