import { Head, Link, router } from '@inertiajs/react';
import {
    Boxes,
    PackagePlus,
    RotateCcw,
    Search,
    SlidersHorizontal,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminFilterOption,
    AdminInventoryFilters,
    AdminInventoryIndexPageProps,
    AdminInventoryListItem,
    AdminPaginationLink,
} from '@/types/admin';

const inventoryIndexUrl = '/admin/inventory';

export default function InventoryIndexPage({
    inventoryItems,
    filters,
    options,
    createComponentUrl,
}: AdminInventoryIndexPageProps) {
    const [filterValues, setFilterValues] = useState<AdminInventoryFilters>({
        search: filters.search ?? '',
        warehouse_id: filters.warehouse_id ?? '',
        state: filters.state ?? '',
        sort: filters.sort ?? 'name',
        per_page: filters.per_page ?? '25',
    });

    function submitFilters(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        router.get(inventoryIndexUrl, normaliseFilters(filterValues), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function resetFilters(): void {
        const resetValues: AdminInventoryFilters = {
            search: '',
            warehouse_id: '',
            state: '',
            sort: 'name',
            per_page: '25',
        };

        setFilterValues(resetValues);

        router.get(inventoryIndexUrl, normaliseFilters(resetValues), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const hasFilters =
        filterValues.search !== '' ||
        filterValues.warehouse_id !== '' ||
        filterValues.state !== '' ||
        filterValues.sort !== 'name' ||
        filterValues.per_page !== '25';

    return (
        <AdminLayout
            title="Inventory"
            description="Search components, inspect stock availability and manage warehouse inventory."
            actions={
                createComponentUrl ? (
                    <Button asChild>
                        <Link href={createComponentUrl}>
                            <PackagePlus className="size-4" />
                            Add component
                        </Link>
                    </Button>
                ) : undefined
            }
        >
            <Head title="Inventory" />

            <div className="space-y-6">
                <InventorySummary
                    items={inventoryItems.data}
                    total={inventoryItems.meta.total}
                />

                <InventoryFilters
                    values={filterValues}
                    options={options}
                    hasFilters={hasFilters}
                    setValues={setFilterValues}
                    onSubmit={submitFilters}
                    onReset={resetFilters}
                />

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <CardTitle>Components</CardTitle>

                                <CardDescription className="mt-1">
                                    {inventoryItems.meta.total === 1
                                        ? '1 inventory item'
                                        : `${inventoryItems.meta.total} inventory items`}
                                </CardDescription>
                            </div>

                            {inventoryItems.meta.from !== null &&
                                inventoryItems.meta.to !== null && (
                                    <p className="text-sm text-muted-foreground">
                                        Showing {inventoryItems.meta.from}–
                                        {inventoryItems.meta.to}
                                    </p>
                                )}
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        {inventoryItems.data.length === 0 ? (
                            <EmptyInventoryState
                                hasFilters={hasFilters}
                                createComponentUrl={createComponentUrl}
                                onReset={resetFilters}
                            />
                        ) : (
                            <>
                                <DesktopInventoryTable
                                    items={inventoryItems.data}
                                />

                                <MobileInventoryList
                                    items={inventoryItems.data}
                                />
                            </>
                        )}
                    </CardContent>
                </Card>

                <Pagination
                    links={inventoryItems.meta.links}
                    currentPage={inventoryItems.meta.current_page}
                    lastPage={inventoryItems.meta.last_page}
                />
            </div>
        </AdminLayout>
    );
}

function InventorySummary({
    items,
    total,
}: {
    items: AdminInventoryListItem[];
    total: number;
}) {
    const lowStockCount = items.filter(
        (item) => item.stock.state === 'low_stock',
    ).length;

    const outOfStockCount = items.filter(
        (item) => item.stock.state === 'out_of_stock',
    ).length;

    const inactiveCount = items.filter(
        (item) => item.stock.state === 'inactive',
    ).length;

    const reservedUnits = items.reduce(
        (totalReserved, item) => totalReserved + item.stock.quantity_reserved,
        0,
    );

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <SummaryCard
                label="Inventory items"
                value={total}
                description="Across all warehouses"
            />

            <SummaryCard
                label="Low stock"
                value={lowStockCount}
                description="On this page"
            />

            <SummaryCard
                label="Out of stock"
                value={outOfStockCount}
                description="On this page"
            />

            <SummaryCard
                label="Reserved units"
                value={reservedUnits}
                description="On this page"
            />

            <SummaryCard
                label="Inactive"
                value={inactiveCount}
                description="On this page"
            />
        </div>
    );
}

function SummaryCard({
    label,
    value,
    description,
}: {
    label: string;
    value: number;
    description: string;
}) {
    return (
        <Card>
            <CardContent className="pt-6">
                <p className="text-sm text-muted-foreground">{label}</p>

                <p className="mt-2 text-3xl font-semibold tracking-tight">
                    {value}
                </p>

                <p className="mt-1 text-xs text-muted-foreground">
                    {description}
                </p>
            </CardContent>
        </Card>
    );
}

type InventoryFilterOptions = {
    warehouses: AdminFilterOption[];
    states: AdminFilterOption[];
    sorts: AdminFilterOption[];
    perPage: AdminFilterOption[];
};

type InventoryFiltersProps = {
    values: AdminInventoryFilters;
    options: InventoryFilterOptions;
    hasFilters: boolean;

    setValues: (values: AdminInventoryFilters) => void;

    onSubmit: (event: FormEvent<HTMLFormElement>) => void;

    onReset: () => void;
};

function InventoryFilters({
    values,
    options,
    hasFilters,
    setValues,
    onSubmit,
    onReset,
}: InventoryFiltersProps) {
    function setFilter<Key extends keyof AdminInventoryFilters>(
        key: Key,
        value: AdminInventoryFilters[Key],
    ): void {
        setValues({
            ...values,
            [key]: value,
        });
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-start gap-3">
                    <span className="grid size-10 shrink-0 place-items-center rounded-xl border bg-muted/20">
                        <SlidersHorizontal className="size-4" />
                    </span>

                    <div>
                        <CardTitle>Filters</CardTitle>

                        <CardDescription className="mt-1">
                            Filter inventory by component, warehouse or stock
                            state.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <form
                    onSubmit={onSubmit}
                    className="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1.6fr)_repeat(4,minmax(10rem,1fr))_auto]"
                >
                    <div>
                        <Label htmlFor="search">Search</Label>

                        <div className="relative mt-2">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                            <Input
                                id="search"
                                value={values.search}
                                className="pl-9"
                                placeholder="Name, SKU or brand"
                                onChange={(event) =>
                                    setFilter('search', event.target.value)
                                }
                            />
                        </div>
                    </div>

                    <FilterSelect
                        id="warehouse_id"
                        label="Warehouse"
                        value={values.warehouse_id}
                        placeholder="All warehouses"
                        options={options.warehouses}
                        allLabel="All warehouses"
                        onChange={(value) =>
                            setFilter(
                                'warehouse_id',
                                value === '__all' ? '' : value,
                            )
                        }
                    />

                    <FilterSelect
                        id="state"
                        label="Stock state"
                        value={values.state}
                        placeholder="All states"
                        options={options.states}
                        allLabel="All states"
                        onChange={(value) =>
                            setFilter('state', value === '__all' ? '' : value)
                        }
                    />

                    <FilterSelect
                        id="sort"
                        label="Sort"
                        value={values.sort}
                        placeholder="Sort"
                        options={options.sorts}
                        onChange={(value) => setFilter('sort', value)}
                    />

                    <FilterSelect
                        id="per_page"
                        label="Per page"
                        value={values.per_page}
                        placeholder="Per page"
                        options={options.perPage}
                        onChange={(value) => setFilter('per_page', value)}
                    />

                    <div className="flex items-end gap-2">
                        <Button type="submit" className="flex-1 xl:flex-none">
                            Apply
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            aria-label="Reset filters"
                            disabled={!hasFilters}
                            onClick={onReset}
                        >
                            <RotateCcw className="size-4" />
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function FilterSelect({
    id,
    label,
    value,
    placeholder,
    options,
    allLabel,
    onChange,
}: {
    id: string;
    label: string;
    value: string;
    placeholder: string;
    options: AdminFilterOption[];
    allLabel?: string;

    onChange: (value: string) => void;
}) {
    return (
        <div>
            <Label htmlFor={id}>{label}</Label>

            <Select
                value={value || (allLabel ? '__all' : undefined)}
                onValueChange={onChange}
            >
                <SelectTrigger id={id} className="mt-2 w-full">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>

                <SelectContent>
                    {allLabel && (
                        <SelectItem value="__all">{allLabel}</SelectItem>
                    )}

                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

function DesktopInventoryTable({ items }: { items: AdminInventoryListItem[] }) {
    return (
        <div className="hidden overflow-x-auto md:block">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Component</TableHead>

                        <TableHead>Warehouse</TableHead>

                        <TableHead className="text-right">On hand</TableHead>

                        <TableHead className="text-right">Reserved</TableHead>

                        <TableHead className="text-right">Available</TableHead>

                        <TableHead>Status</TableHead>

                        <TableHead className="text-right">Price</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    {items.map((item) => (
                        <TableRow key={item.id}>
                            <TableCell>
                                <ComponentIdentity item={item} />
                            </TableCell>

                            <TableCell>
                                <p className="font-medium">
                                    {item.warehouse.name}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    {item.warehouse.code}

                                    {item.bin_location
                                        ? ` · ${item.bin_location}`
                                        : ''}
                                </p>
                            </TableCell>

                            <TableCell className="text-right font-medium tabular-nums">
                                {item.stock.quantity_on_hand}
                            </TableCell>

                            <TableCell className="text-right text-muted-foreground tabular-nums">
                                {item.stock.quantity_reserved}
                            </TableCell>

                            <TableCell className="text-right font-medium tabular-nums">
                                {item.stock.available}
                            </TableCell>

                            <TableCell>
                                <StockStateBadge state={item.stock.state} />
                            </TableCell>

                            <TableCell className="text-right">
                                {formatPrice(item.price)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function MobileInventoryList({ items }: { items: AdminInventoryListItem[] }) {
    return (
        <div className="divide-y md:hidden">
            {items.map((item) => (
                <article key={item.id} className="p-4">
                    <ComponentIdentity item={item} />

                    <div className="mt-4 grid grid-cols-2 gap-4 rounded-xl border bg-muted/10 p-4 text-sm">
                        <MobileValue
                            label="Warehouse"
                            value={`${item.warehouse.name} (${item.warehouse.code})`}
                        />

                        <MobileValue
                            label="Bin"
                            value={item.bin_location ?? '—'}
                        />

                        <MobileValue
                            label="On hand"
                            value={item.stock.quantity_on_hand.toString()}
                        />

                        <MobileValue
                            label="Reserved"
                            value={item.stock.quantity_reserved.toString()}
                        />

                        <MobileValue
                            label="Available"
                            value={item.stock.available.toString()}
                        />

                        <MobileValue
                            label="Price"
                            value={formatPrice(item.price)}
                        />
                    </div>

                    <div className="mt-4">
                        <StockStateBadge state={item.stock.state} />
                    </div>
                </article>
            ))}
        </div>
    );
}

function ComponentIdentity({ item }: { item: AdminInventoryListItem }) {
    return (
        <Link
            href={item.href}
            className="flex min-w-0 items-center gap-4 rounded-lg transition outline-none hover:opacity-80 focus-visible:ring-2 focus-visible:ring-ring"
        >
            <div className="grid size-14 shrink-0 place-items-center overflow-hidden rounded-xl border bg-muted/20">
                {item.image?.url ? (
                    <img
                        src={item.image.url}
                        alt={item.image.alt}
                        className="size-full object-contain p-1.5"
                    />
                ) : (
                    <Boxes className="size-5 text-muted-foreground" />
                )}
            </div>

            <div className="min-w-0">
                <p className="truncate font-medium">{item.product.name}</p>

                <p className="mt-1 truncate text-sm text-muted-foreground">
                    {item.product.brand ?? 'No brand'}
                    {item.variant.name ? ` · ${item.variant.name}` : ''}
                </p>

                <p className="mt-1 truncate font-mono text-xs text-muted-foreground">
                    {item.variant.sku}
                </p>
            </div>
        </Link>
    );
}

function MobileValue({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>

            <p className="mt-1 font-medium">{value}</p>
        </div>
    );
}

function StockStateBadge({
    state,
}: {
    state: AdminInventoryListItem['stock']['state'];
}) {
    if (state === 'in_stock') {
        return <Badge variant="outline">In stock</Badge>;
    }

    if (state === 'low_stock') {
        return <Badge variant="secondary">Low stock</Badge>;
    }

    if (state === 'out_of_stock') {
        return <Badge variant="destructive">Out of stock</Badge>;
    }

    return <Badge variant="outline">Inactive</Badge>;
}

function EmptyInventoryState({
    hasFilters,
    createComponentUrl,
    onReset,
}: {
    hasFilters: boolean;
    createComponentUrl: string | null;
    onReset: () => void;
}) {
    return (
        <div className="px-6 py-16 text-center">
            <span className="mx-auto grid size-12 place-items-center rounded-xl border bg-muted/20">
                <Boxes className="size-5" />
            </span>

            <h2 className="mt-5 text-lg font-semibold">
                {hasFilters
                    ? 'No inventory items match these filters'
                    : 'No inventory items yet'}
            </h2>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                {hasFilters
                    ? 'Change or reset the current filters to see more results.'
                    : 'Create the first catalogue component and assign it to a warehouse.'}
            </p>

            <div className="mt-6 flex flex-wrap justify-center gap-3">
                {hasFilters && (
                    <Button type="button" variant="outline" onClick={onReset}>
                        <RotateCcw className="size-4" />
                        Reset filters
                    </Button>
                )}

                {createComponentUrl && (
                    <Button asChild>
                        <Link href={createComponentUrl}>
                            <PackagePlus className="size-4" />
                            Add component
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}

function Pagination({
    links,
    currentPage,
    lastPage,
}: {
    links: AdminPaginationLink[];
    currentPage: number;
    lastPage: number;
}) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                Page {currentPage} of {lastPage}
            </p>

            <nav
                className="flex flex-wrap items-center justify-center gap-2"
                aria-label="Inventory pagination"
            >
                {links.map((link, index) => {
                    const label = normalisePaginationLabel(link.label);

                    if (link.url === null) {
                        return (
                            <Button
                                key={`${link.label}-${index}`}
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled
                            >
                                {label}
                            </Button>
                        );
                    }

                    return (
                        <Button
                            key={`${link.label}-${index}`}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            asChild
                        >
                            <Link href={link.url} preserveScroll preserveState>
                                {label}
                            </Link>
                        </Button>
                    );
                })}
            </nav>
        </div>
    );
}

function normaliseFilters(
    filters: AdminInventoryFilters,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== ''),
    );
}

function normalisePaginationLabel(label: string): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', 'Previous')
        .replace('Next', 'Next');
}

function formatPrice(
    price: AdminInventoryListItem['price'] | undefined,
): string {
    if (!price) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: price.currency,
    }).format(price.amount_in_cents / 100);
}
