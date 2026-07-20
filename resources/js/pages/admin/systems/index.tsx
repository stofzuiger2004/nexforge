import { Head, Link } from '@inertiajs/react';
import { ExternalLink, Settings2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    AdminPaginationLink,
    AdminSystemIndexPageProps,
    AdminSystemListItem,
} from '@/types/admin';

export default function SystemIndexPage({
    systems,
}: AdminSystemIndexPageProps) {
    return (
        <AdminLayout
            title="System presets"
            description="Choose the default component for every configurator slot in each prebuilt system."
        >
            <Head title="System presets" />

            <div className="space-y-6">
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>Systems</CardTitle>
                        <CardDescription>
                            {systems.meta.total === 1
                                ? '1 system preset'
                                : `${systems.meta.total} system presets`}
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="p-0">
                        {systems.data.length === 0 ? (
                            <div className="px-6 py-16 text-center">
                                <p className="font-medium">
                                    No systems available
                                </p>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Create or seed a system before assigning
                                    default components.
                                </p>
                            </div>
                        ) : (
                            <SystemTable systems={systems.data} />
                        )}
                    </CardContent>
                </Card>

                <Pagination links={systems.meta.links} />
            </div>
        </AdminLayout>
    );
}

function SystemTable({ systems }: { systems: AdminSystemListItem[] }) {
    return (
        <div className="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>System</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Defaults</TableHead>
                        <TableHead>Prices</TableHead>
                        <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    {systems.map((system) => (
                        <TableRow key={system.id}>
                            <TableCell>
                                <p className="font-medium">{system.name}</p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {system.sku}
                                </p>
                            </TableCell>

                            <TableCell>
                                <div className="flex flex-wrap gap-2">
                                    <StatusBadge status={system.status} />

                                    {!system.is_configurable && (
                                        <Badge variant="outline">
                                            Configurator disabled
                                        </Badge>
                                    )}

                                    {system.published_at === null && (
                                        <Badge variant="outline">
                                            Unpublished
                                        </Badge>
                                    )}
                                </div>
                            </TableCell>

                            <TableCell>
                                {system.component_count}{' '}
                                {system.component_count === 1
                                    ? 'component'
                                    : 'components'}
                            </TableCell>

                            <TableCell>
                                <div className="space-y-1">
                                    {system.prices.length === 0 ? (
                                        <span className="text-sm text-muted-foreground">
                                            No system price
                                        </span>
                                    ) : (
                                        system.prices.map((price) => (
                                            <p
                                                key={price.price_list_id}
                                                className="text-sm"
                                            >
                                                {price.price_list_name}:{' '}
                                                {formatMoney(
                                                    price.amount_in_cents,
                                                    price.currency,
                                                )}
                                            </p>
                                        ))
                                    )}
                                </div>
                            </TableCell>

                            <TableCell>
                                <div className="flex justify-end gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={system.storefront_url}>
                                            <ExternalLink className="size-4" />
                                            View
                                        </Link>
                                    </Button>

                                    <Button size="sm" asChild>
                                        <Link href={system.edit_url}>
                                            <Settings2 className="size-4" />
                                            Edit preset
                                        </Link>
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function StatusBadge({ status }: { status: AdminSystemListItem['status'] }) {
    if (status === 'active') {
        return <Badge>Active</Badge>;
    }

    if (status === 'archived') {
        return <Badge variant="secondary">Archived</Badge>;
    }

    return <Badge variant="outline">Draft</Badge>;
}

function Pagination({ links }: { links: AdminPaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap justify-center gap-2">
            {links.map((link, index) => {
                const label = link.label
                    .replace('&laquo;', '‹')
                    .replace('&raquo;', '›');

                return link.url === null ? (
                    <Button
                        key={`${label}-${index}`}
                        variant="outline"
                        size="sm"
                        disabled
                    >
                        {label}
                    </Button>
                ) : (
                    <Button
                        key={`${label}-${index}`}
                        variant={link.active ? 'default' : 'outline'}
                        size="sm"
                        asChild
                    >
                        <Link href={link.url} preserveScroll>
                            {label}
                        </Link>
                    </Button>
                );
            })}
        </div>
    );
}

function formatMoney(amountInCents: number, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amountInCents / 100);
}
