import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    ExternalLink,
    PackageCheck,
    Save,
} from 'lucide-react';
import type { FormEvent } from 'react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminSystemCompatibilityIssue,
    AdminSystemEditPageProps,
    AdminSystemPricingSummary,
    AdminSystemSlotPreset,
    AdminSystemVariantOption,
} from '@/types/admin';

export default function SystemEditPage({
    system,
    slots,
    compatibility,
    pricing,
    can,
}: AdminSystemEditPageProps) {
    const failedResults = compatibility.results.filter(
        (result) => result.status === 'failed',
    );

    return (
        <AdminLayout
            title={system.name}
            description="Assign the component that is copied into a new configuration for every system slot."
            actions={
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" asChild>
                        <Link href={system.index_url}>
                            <ArrowLeft className="size-4" />
                            All systems
                        </Link>
                    </Button>

                    <Button variant="outline" asChild>
                        <Link href={system.storefront_url}>
                            <ExternalLink className="size-4" />
                            Storefront
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title={`Edit ${system.name}`} />

            <div className="space-y-6">
                <SystemOverview
                    system={system}
                    pricing={pricing}
                    canManagePrices={can.manage_prices}
                />

                <CompatibilityPanel
                    compatible={compatibility.is_compatible}
                    issues={failedResults}
                />

                <div className="grid gap-6 xl:grid-cols-2">
                    {slots.map((slot) => (
                        <SystemSlotEditor key={slot.value} slot={slot} />
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}

function SystemOverview({
    system,
    pricing,
    canManagePrices,
}: Pick<AdminSystemEditPageProps, 'system' | 'pricing'> & {
    canManagePrices: boolean;
}) {
    return (
        <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <Card>
                <CardHeader>
                    <CardTitle>Preset status</CardTitle>
                    <CardDescription>
                        Catalogue and publication state for this system.
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-3 text-sm">
                    <DetailRow label="SKU" value={system.sku} />
                    <DetailRow label="Status" value={system.status} />
                    <DetailRow
                        label="Configurator"
                        value={system.is_configurable ? 'Enabled' : 'Disabled'}
                    />
                    <DetailRow
                        label="Published"
                        value={
                            system.published_at === null
                                ? 'No'
                                : new Date(system.published_at).toLocaleString()
                        }
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Pricing effect</CardTitle>
                    <CardDescription>
                        Change the fixed package price for each price list. The
                        package adjustment is recalculated from that price and
                        the currently selected default components.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    {pricing.length === 0 ? (
                        <Alert variant="destructive">
                            <AlertTriangle />
                            <AlertTitle>No system price</AlertTitle>
                            <AlertDescription>
                                Add a system price before assigning preset
                                components.
                            </AlertDescription>
                        </Alert>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {pricing.map((item) => (
                                <SystemPriceEditor
                                    key={`${item.price_list_id}:${item.lock_version}`}
                                    item={item}
                                    canManage={canManagePrices}
                                />
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

type SystemPriceForm = {
    amount: string;
    compare_at_amount: string;
    reason: string;
    expected_lock_version: number;
};

function SystemPriceEditor({
    item,
    canManage,
}: {
    item: AdminSystemPricingSummary;
    canManage: boolean;
}) {
    const form = useForm<SystemPriceForm>({
        amount: centsToDecimal(item.system_price_in_cents),
        compare_at_amount:
            item.compare_at_amount_in_cents === null
                ? ''
                : centsToDecimal(item.compare_at_amount_in_cents),
        reason: '',
        expected_lock_version: item.lock_version,
    });

    const enteredAmountInCents = decimalToCents(form.data.amount);
    const previewAdjustmentInCents =
        enteredAmountInCents !== null &&
        item.component_subtotal_in_cents !== null
            ? enteredAmountInCents - item.component_subtotal_in_cents
            : null;

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        form.patch(item.update_url, {
            preserveScroll: true,
        });
    }

    if (!canManage) {
        return (
            <div className="rounded-xl border p-4">
                <p className="font-medium">{item.price_list_name}</p>

                <dl className="mt-3 space-y-2 text-sm">
                    <DetailRow
                        label="System price"
                        value={formatMoney(
                            item.system_price_in_cents,
                            item.currency,
                        )}
                    />
                    <DetailRow
                        label="Components"
                        value={componentSubtotalLabel(item)}
                    />
                    <DetailRow
                        label="Package adjustment"
                        value={packageAdjustmentLabel(item)}
                    />
                </dl>
            </div>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-4 rounded-xl border p-4"
        >
            <div>
                <p className="font-medium">{item.price_list_name}</p>
                <p className="mt-1 text-xs text-muted-foreground">
                    Currency: {item.currency}
                </p>
            </div>

            <div>
                <Label htmlFor={`system-price-${item.price_list_id}`}>
                    Package price
                </Label>
                <Input
                    id={`system-price-${item.price_list_id}`}
                    className="mt-2"
                    type="text"
                    inputMode="decimal"
                    value={form.data.amount}
                    aria-invalid={form.errors.amount !== undefined}
                    onChange={(event) =>
                        form.setData('amount', event.target.value)
                    }
                />
                {form.errors.amount && (
                    <p className="mt-2 text-sm text-destructive">
                        {form.errors.amount}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor={`compare-price-${item.price_list_id}`}>
                    Compare-at price
                </Label>
                <Input
                    id={`compare-price-${item.price_list_id}`}
                    className="mt-2"
                    type="text"
                    inputMode="decimal"
                    placeholder="Optional"
                    value={form.data.compare_at_amount}
                    aria-invalid={
                        form.errors.compare_at_amount !== undefined
                    }
                    onChange={(event) =>
                        form.setData(
                            'compare_at_amount',
                            event.target.value,
                        )
                    }
                />
                {form.errors.compare_at_amount && (
                    <p className="mt-2 text-sm text-destructive">
                        {form.errors.compare_at_amount}
                    </p>
                )}
            </div>

            <div>
                <Label htmlFor={`price-reason-${item.price_list_id}`}>
                    Reason for change
                </Label>
                <Input
                    id={`price-reason-${item.price_list_id}`}
                    className="mt-2"
                    type="text"
                    placeholder="For example: Updated preset pricing"
                    value={form.data.reason}
                    aria-invalid={form.errors.reason !== undefined}
                    onChange={(event) =>
                        form.setData('reason', event.target.value)
                    }
                />
                {form.errors.reason && (
                    <p className="mt-2 text-sm text-destructive">
                        {form.errors.reason}
                    </p>
                )}
                {form.errors.expected_lock_version && (
                    <p className="mt-2 text-sm text-destructive">
                        {form.errors.expected_lock_version}
                    </p>
                )}
            </div>

            <dl className="space-y-2 border-t pt-4 text-sm">
                <DetailRow
                    label="Components"
                    value={componentSubtotalLabel(item)}
                />
                <DetailRow
                    label="Current adjustment"
                    value={packageAdjustmentLabel(item)}
                />
                <DetailRow
                    label="New adjustment"
                    value={
                        previewAdjustmentInCents === null
                            ? 'Unavailable'
                            : formatMoney(
                                  previewAdjustmentInCents,
                                  item.currency,
                              )
                    }
                />
            </dl>

            {item.missing_price_skus.length > 0 && (
                <p className="text-xs text-destructive">
                    Missing component prices:{' '}
                    {item.missing_price_skus.join(', ')}
                </p>
            )}

            <Button
                type="submit"
                className="w-full"
                disabled={
                    form.processing ||
                    enteredAmountInCents === null ||
                    form.data.reason.trim().length < 5
                }
            >
                <Save className="size-4" />
                {form.processing ? 'Saving…' : 'Save package price'}
            </Button>
        </form>
    );
}

function componentSubtotalLabel(item: AdminSystemPricingSummary): string {
    return item.component_subtotal_in_cents === null
        ? 'Incomplete'
        : formatMoney(item.component_subtotal_in_cents, item.currency);
}

function packageAdjustmentLabel(item: AdminSystemPricingSummary): string {
    return item.package_adjustment_in_cents === null
        ? 'Unavailable'
        : formatMoney(item.package_adjustment_in_cents, item.currency);
}

function CompatibilityPanel({
    compatible,
    issues,
}: {
    compatible: boolean;
    issues: AdminSystemCompatibilityIssue[];
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex items-start gap-3">
                    <span className="grid size-10 shrink-0 place-items-center rounded-xl border bg-muted/20">
                        {compatible ? (
                            <CheckCircle2 className="size-5" />
                        ) : (
                            <AlertTriangle className="size-5" />
                        )}
                    </span>

                    <div>
                        <CardTitle>Preset compatibility</CardTitle>
                        <CardDescription className="mt-1">
                            Compatibility is recalculated after every saved
                            default. Incompatible replacements are rejected by
                            the server.
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                {issues.length === 0 ? (
                    <Alert>
                        <PackageCheck />
                        <AlertTitle>Preset checks passed</AlertTitle>
                        <AlertDescription>
                            No active compatibility rule currently reports a
                            problem.
                        </AlertDescription>
                    </Alert>
                ) : (
                    <div className="space-y-3">
                        {issues.map((issue) => (
                            <Alert
                                key={issue.rule_key}
                                variant={
                                    issue.severity === 'error'
                                        ? 'destructive'
                                        : 'default'
                                }
                            >
                                <AlertTriangle />
                                <AlertTitle>
                                    {issue.rule_name}{' '}
                                    <Badge
                                        variant={
                                            issue.severity === 'error'
                                                ? 'destructive'
                                                : 'outline'
                                        }
                                    >
                                        {issue.severity}
                                    </Badge>
                                </AlertTitle>
                                <AlertDescription>
                                    {issue.message ??
                                        'This compatibility rule failed.'}
                                </AlertDescription>
                            </Alert>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

type SlotForm = {
    variant_id: string;
    quantity: string;
    is_required: boolean;
    is_replaceable: boolean;
};

function SystemSlotEditor({ slot }: { slot: AdminSystemSlotPreset }) {
    const form = useForm<SlotForm>({
        variant_id: slot.current?.variant_id.toString() ?? '',
        quantity: slot.current?.quantity.toString() ?? '1',
        is_required: slot.current?.is_required ?? true,
        is_replaceable: slot.current?.is_replaceable ?? true,
    });

    const selectedOption = slot.options.find(
        (option) => option.id.toString() === form.data.variant_id,
    );

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        form.put(slot.update_url, {
            preserveScroll: true,
        });
    }

    return (
        <Card>
            <CardHeader className="border-b">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <CardTitle>{slot.label}</CardTitle>
                        <CardDescription className="mt-1">
                            {slot.description}
                        </CardDescription>
                    </div>

                    {slot.current === null ? (
                        <Badge variant="outline">Not assigned</Badge>
                    ) : (
                        <Badge variant="secondary">Assigned</Badge>
                    )}
                </div>
            </CardHeader>

            <CardContent className="pt-6">
                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <Label htmlFor={`${slot.value}-variant`}>
                            Default component
                        </Label>

                        <Select
                            value={form.data.variant_id}
                            onValueChange={(value) => {
                                form.setData('variant_id', value);
                                form.clearErrors('variant_id');
                            }}
                        >
                            <SelectTrigger
                                id={`${slot.value}-variant`}
                                className="mt-2 w-full"
                                aria-invalid={
                                    form.errors.variant_id !== undefined
                                }
                            >
                                <SelectValue placeholder="Select a component" />
                            </SelectTrigger>

                            <SelectContent align="start">
                                {slot.options.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={option.id.toString()}
                                        disabled={
                                            option.disabled_reason !== null &&
                                            !option.selected
                                        }
                                    >
                                        {option.name} · {option.sku}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {form.errors.variant_id && (
                            <p className="mt-2 text-sm text-destructive">
                                {form.errors.variant_id}
                            </p>
                        )}
                    </div>

                    {selectedOption && (
                        <VariantSummary option={selectedOption} />
                    )}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <Label htmlFor={`${slot.value}-quantity`}>
                                Quantity
                            </Label>
                            <Input
                                id={`${slot.value}-quantity`}
                                type="number"
                                min={1}
                                max={100}
                                className="mt-2"
                                value={form.data.quantity}
                                disabled={!slot.allows_multiple}
                                onChange={(event) =>
                                    form.setData('quantity', event.target.value)
                                }
                            />

                            {!slot.allows_multiple && (
                                <p className="mt-2 text-xs text-muted-foreground">
                                    This slot always uses one component.
                                </p>
                            )}
                        </div>

                        <div className="space-y-3 pt-1 sm:pt-7">
                            <label className="flex items-center gap-3 text-sm">
                                <Checkbox
                                    checked={form.data.is_required}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_required',
                                            checked === true,
                                        )
                                    }
                                />
                                Required in the preset
                            </label>

                            <label className="flex items-center gap-3 text-sm">
                                <Checkbox
                                    checked={form.data.is_replaceable}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'is_replaceable',
                                            checked === true,
                                        )
                                    }
                                />
                                Customer may replace it
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end border-t pt-5">
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                form.data.variant_id === '' ||
                                selectedOption?.disabled_reason !== null
                            }
                        >
                            <Save className="size-4" />
                            {form.processing ? 'Saving…' : 'Save default'}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

function VariantSummary({ option }: { option: AdminSystemVariantOption }) {
    return (
        <div className="rounded-xl border bg-muted/20 p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-medium">{option.name}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {[option.brand, option.category.name, option.sku]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </div>

                {option.inventory.tracked ? (
                    <Badge
                        variant={
                            option.inventory.reservable > 0
                                ? 'secondary'
                                : 'outline'
                        }
                    >
                        {option.inventory.reservable} reservable
                    </Badge>
                ) : (
                    <Badge variant="outline">Inventory not tracked</Badge>
                )}
            </div>

            <div className="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                {option.prices.map((price) => (
                    <span key={price.price_list_id}>
                        {price.price_list_name}:{' '}
                        <strong>
                            {formatMoney(price.amount_in_cents, price.currency)}
                        </strong>
                    </span>
                ))}
            </div>

            {option.disabled_reason && (
                <p className="mt-3 text-sm text-destructive">
                    {option.disabled_reason}
                </p>
            )}
        </div>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium capitalize">{value}</dd>
        </div>
    );
}

function centsToDecimal(amountInCents: number): string {
    return (amountInCents / 100).toFixed(2);
}

function decimalToCents(value: string): number | null {
    const normalized = value.trim().replace(',', '.');

    if (!/^\d{1,9}(?:\.\d{1,2})?$/.test(normalized)) {
        return null;
    }

    const [major, minor = ''] = normalized.split('.');
    const paddedMinor = minor.padEnd(2, '0');

    return Number.parseInt(major, 10) * 100 + Number.parseInt(paddedMinor, 10);
}

function formatMoney(amountInCents: number, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(amountInCents / 100);
}
