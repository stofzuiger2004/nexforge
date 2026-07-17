import { Head, router, useForm } from '@inertiajs/react';
import { RotateCcw, Search } from 'lucide-react';
import type { FormEvent } from 'react';

import { AdminPagination } from '@/components/admin/admin-pagination';
import { OrdersTable } from '@/components/admin/orders-table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminFilterOption,
    AdminOrderFilters,
    AdminOrderPaginator,
} from '@/types/admin';

type OrdersIndexProps = {
    orders: AdminOrderPaginator;

    filters: {
        search: string | null;
        status: string | null;
        payment_status: string | null;
        fulfillment_status: string | null;
        date_from: string | null;
        date_to: string | null;
        sort: string;
        per_page: number;
    };

    filterOptions: {
        statuses: AdminFilterOption[];
        payment_statuses: AdminFilterOption[];
        fulfillment_statuses: AdminFilterOption[];
        sorts: AdminFilterOption[];
        per_page: number[];
    };
};

export default function OrdersIndex({
    orders,
    filters,
    filterOptions,
}: OrdersIndexProps) {
    const form = useForm<AdminOrderFilters>({
        search: filters.search ?? '',
        status: filters.status ?? '',
        payment_status: filters.payment_status ?? '',
        fulfillment_status: filters.fulfillment_status ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
        sort: filters.sort,
        per_page: String(filters.per_page),
    });

    function applyFilters(event: FormEvent) {
        event.preventDefault();

        form.get('/admin/orders', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function resetFilters() {
        router.get(
            '/admin/orders',
            {},
            {
                replace: true,
            },
        );
    }

    return (
        <AdminLayout
            title="Orders"
            description="Search, filter, and inspect every order and its related payment and inventory information."
        >
            <Head title="Admin orders" />

            <form
                onSubmit={applyFilters}
                className="rounded-2xl border bg-background p-5"
            >
                <div className="grid gap-4 lg:grid-cols-4">
                    <div className="relative lg:col-span-2">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />

                        <Input
                            value={form.data.search}
                            className="pl-9"
                            placeholder="Order, customer email, name, or payment ID"
                            onChange={(event) =>
                                form.setData('search', event.target.value)
                            }
                        />
                    </div>

                    <FilterSelect
                        value={form.data.status}
                        placeholder="All order statuses"
                        options={filterOptions.statuses}
                        onChange={(value) => form.setData('status', value)}
                    />

                    <FilterSelect
                        value={form.data.payment_status}
                        placeholder="All payment statuses"
                        options={filterOptions.payment_statuses}
                        onChange={(value) =>
                            form.setData('payment_status', value)
                        }
                    />

                    <FilterSelect
                        value={form.data.fulfillment_status}
                        placeholder="All fulfilment statuses"
                        options={filterOptions.fulfillment_statuses}
                        onChange={(value) =>
                            form.setData('fulfillment_status', value)
                        }
                    />

                    <Input
                        type="date"
                        value={form.data.date_from}
                        aria-label="From date"
                        onChange={(event) =>
                            form.setData('date_from', event.target.value)
                        }
                    />

                    <Input
                        type="date"
                        value={form.data.date_to}
                        aria-label="To date"
                        onChange={(event) =>
                            form.setData('date_to', event.target.value)
                        }
                    />

                    <FilterSelect
                        value={form.data.sort}
                        placeholder="Sort"
                        options={filterOptions.sorts}
                        allowAll={false}
                        onChange={(value) => form.setData('sort', value)}
                    />

                    <Select
                        value={form.data.per_page}
                        onValueChange={(value) =>
                            form.setData('per_page', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            {filterOptions.per_page.map((value) => (
                                <SelectItem key={value} value={String(value)}>
                                    {value} per page
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="mt-5 flex flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={resetFilters}
                    >
                        <RotateCcw className="size-4" />
                        Reset
                    </Button>

                    <Button type="submit">Apply filters</Button>
                </div>
            </form>

            <div className="mt-6">
                <div className="mb-4 flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                        {orders.meta.total} order
                        {orders.meta.total === 1 ? '' : 's'}
                    </p>
                </div>

                <OrdersTable orders={orders.data} />

                <div className="mt-6">
                    <AdminPagination meta={orders.meta} links={orders.links} />
                </div>
            </div>
        </AdminLayout>
    );
}

type FilterSelectProps = {
    value: string;
    placeholder: string;
    options: AdminFilterOption[];
    allowAll?: boolean;
    onChange: (value: string) => void;
};

function FilterSelect({
    value,
    placeholder,
    options,
    allowAll = true,
    onChange,
}: FilterSelectProps) {
    return (
        <Select
            value={value || 'all'}
            onValueChange={(selected) =>
                onChange(selected === 'all' ? '' : selected)
            }
        >
            <SelectTrigger>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>

            <SelectContent>
                {allowAll && <SelectItem value="all">{placeholder}</SelectItem>}

                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
