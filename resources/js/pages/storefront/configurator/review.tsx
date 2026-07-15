import {
    Head,
    Link,
    router,
    useForm,
} from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    ChevronRight,
    LoaderCircle,
    ShieldCheck,
} from 'lucide-react';
import {
    useCallback,
    useState,
} from 'react';

import { AddressFields } from '@/components/checkout/address-fields';
import { ReservationCountdown } from '@/components/configurator/reservation-countdown';
import { SystemVisual } from '@/components/storefront/system-visual';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatMoney } from '@/lib/money';
import type {
    CheckoutFormData,
    ConfigurationReviewPageProps,
} from '@/types/checkout';

import type { FormEvent } from 'react';

export default function ConfigurationReviewPage({
    review,
    checkout_defaults,
    countries,
}: ConfigurationReviewPageProps) {
    const [reservationExpired, setReservationExpired] =
        useState(
            review.reservation.is_expired,
        );

    const [refreshing, setRefreshing] =
        useState(false);

    const [returning, setReturning] =
        useState(false);

    const form = useForm<CheckoutFormData>(
        checkout_defaults,
    );

    const errors =
        form.errors as Record<
            string,
            string
        >;

    const markExpired = useCallback(
        () => {
            setReservationExpired(true);
        },
        [],
    );

    function submit(
        event: FormEvent,
    ) {
        event.preventDefault();

        if (
            reservationExpired
            || ! review.can_submit
        ) {
            return;
        }

        form.post(
            `/configure/${review.configuration.public_id}/order`,
            {
                preserveScroll: 'errors',
            },
        );
    }

    function refreshReservation() {
        router.post(
            `/configure/${review.configuration.public_id}/review`,
            {},
            {
                preserveScroll: true,

                onStart: () =>
                    setRefreshing(true),

                onFinish: () =>
                    setRefreshing(false),
            },
        );
    }

    function returnToConfigurator() {
        router.delete(
            `/configure/${review.configuration.public_id}/review`,
            {
                onStart: () =>
                    setReturning(true),

                onFinish: () =>
                    setReturning(false),
            },
        );
    }

    return (
        <StorefrontLayout>
            <Head title="Review your configuration" />

            <ReviewHeader
                configurationName={
                    review.configuration.name
                }
            />

            <section className="py-10 sm:py-14">
                <div className="mx-auto grid max-w-7xl items-start gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_23rem] lg:px-8">
                    <form
                        className="space-y-8"
                        onSubmit={submit}
                    >
                        <div className="rounded-2xl border p-5 sm:p-6">
                            <div className="flex items-start gap-4">
                                <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-muted">
                                    <ShieldCheck className="size-5" />
                                </span>

                                <div>
                                    <h2 className="font-semibold">
                                        {
                                            review.validation
                                                .label
                                        }
                                    </h2>

                                    <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                        The component
                                        selection passed
                                        compatibility
                                        validation before
                                        stock was reserved.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <SelectedComponents
                            review={review}
                        />

                        <section className="rounded-2xl border p-5 sm:p-6">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">
                                    Customer details
                                </p>

                                <h2 className="mt-2 text-2xl font-semibold">
                                    Contact information
                                </h2>
                            </div>

                            <div className="mt-6 grid gap-5 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="checkout-email">
                                        Email address
                                    </Label>

                                    <Input
                                        id="checkout-email"
                                        type="email"
                                        autoComplete="email"
                                        value={
                                            form.data
                                                .email
                                        }
                                        disabled={
                                            form.processing
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'email',
                                                event
                                                    .target
                                                    .value,
                                            )
                                        }
                                    />

                                    {errors.email && (
                                        <p className="text-xs text-destructive">
                                            {
                                                errors.email
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="checkout-phone">
                                        Phone number
                                    </Label>

                                    <Input
                                        id="checkout-phone"
                                        type="tel"
                                        autoComplete="tel"
                                        value={
                                            form.data
                                                .phone
                                        }
                                        disabled={
                                            form.processing
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'phone',
                                                event
                                                    .target
                                                    .value,
                                            )
                                        }
                                    />

                                    {errors.phone && (
                                        <p className="text-xs text-destructive">
                                            {
                                                errors.phone
                                            }
                                        </p>
                                    )}
                                </div>
                            </div>
                        </section>

                        <section className="rounded-2xl border p-5 sm:p-6">
                            <p className="text-sm font-medium text-muted-foreground">
                                Delivery
                            </p>

                            <h2 className="mt-2 text-2xl font-semibold">
                                Shipping address
                            </h2>

                            <div className="mt-6">
                                <AddressFields
                                    idPrefix="shipping"
                                    value={
                                        form.data
                                            .shipping
                                    }
                                    countries={
                                        countries
                                    }
                                    errors={
                                        errors
                                    }
                                    fieldPrefix="shipping"
                                    disabled={
                                        form.processing
                                    }
                                    onChange={(
                                        value,
                                    ) =>
                                        form.setData(
                                            'shipping',
                                            value,
                                        )
                                    }
                                />
                            </div>
                        </section>

                        <section className="rounded-2xl border p-5 sm:p-6">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="billing-same"
                                    checked={
                                        form.data
                                            .billing_same_as_shipping
                                    }
                                    disabled={
                                        form.processing
                                    }
                                    onCheckedChange={(
                                        checked,
                                    ) =>
                                        form.setData(
                                            'billing_same_as_shipping',
                                            checked
                                                === true,
                                        )
                                    }
                                />

                                <div>
                                    <Label
                                        htmlFor="billing-same"
                                        className="cursor-pointer"
                                    >
                                        Billing address is
                                        the same as the
                                        shipping address
                                    </Label>

                                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                        Disable this when
                                        the invoice requires
                                        a different address.
                                    </p>
                                </div>
                            </div>

                            {! form.data
                                .billing_same_as_shipping && (
                                <div className="mt-7 border-t pt-7">
                                    <h2 className="mb-6 text-xl font-semibold">
                                        Billing address
                                    </h2>

                                    <AddressFields
                                        idPrefix="billing"
                                        value={
                                            form.data
                                                .billing
                                        }
                                        countries={
                                            countries
                                        }
                                        errors={
                                            errors
                                        }
                                        fieldPrefix="billing"
                                        disabled={
                                            form.processing
                                        }
                                        onChange={(
                                            value,
                                        ) =>
                                            form.setData(
                                                'billing',
                                                value,
                                            )
                                        }
                                    />
                                </div>
                            )}
                        </section>

                        <section className="rounded-2xl border p-5 sm:p-6">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="checkout-terms"
                                    checked={
                                        form.data
                                            .terms
                                    }
                                    disabled={
                                        form.processing
                                    }
                                    onCheckedChange={(
                                        checked,
                                    ) =>
                                        form.setData(
                                            'terms',
                                            checked
                                                === true,
                                        )
                                    }
                                />

                                <div>
                                    <Label
                                        htmlFor="checkout-terms"
                                        className="cursor-pointer"
                                    >
                                        I accept the terms
                                        and privacy policy
                                    </Label>

                                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                        Review the{' '}
                                        <Link
                                            href="/terms"
                                            className="underline underline-offset-4"
                                        >
                                            terms
                                        </Link>{' '}
                                        and{' '}
                                        <Link
                                            href="/privacy"
                                            className="underline underline-offset-4"
                                        >
                                            privacy policy
                                        </Link>
                                        .
                                    </p>

                                    {errors.terms && (
                                        <p className="mt-2 text-xs text-destructive">
                                            {
                                                errors.terms
                                            }
                                        </p>
                                    )}
                                </div>
                            </div>
                        </section>

                        {errors.checkout && (
                            <p className="rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                                {
                                    errors.checkout
                                }
                            </p>
                        )}

                        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={
                                    returning
                                    || form.processing
                                }
                                onClick={
                                    returnToConfigurator
                                }
                            >
                                {returning ? (
                                    <LoaderCircle className="size-4 animate-spin" />
                                ) : (
                                    <ArrowLeft className="size-4" />
                                )}

                                Return to configuration
                            </Button>

                            <Button
                                type="submit"
                                size="lg"
                                disabled={
                                    form.processing
                                    || reservationExpired
                                    || ! review.can_submit
                                }
                            >
                                {form.processing ? (
                                    <>
                                        <LoaderCircle className="size-4 animate-spin" />
                                        Creating order
                                    </>
                                ) : (
                                    'Continue to payment'
                                )}
                            </Button>
                        </div>
                    </form>

                    <aside>
                        <div className="sticky top-24 space-y-4">
                            <ReviewSummary
                                review={review}
                            />

                            <ReservationCountdown
                                expiresAt={
                                    review
                                        .reservation
                                        .expires_at
                                }
                                onExpired={
                                    markExpired
                                }
                            />

                            {reservationExpired && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="w-full"
                                    disabled={
                                        refreshing
                                    }
                                    onClick={
                                        refreshReservation
                                    }
                                >
                                    {refreshing && (
                                        <LoaderCircle className="size-4 animate-spin" />
                                    )}

                                    Refresh stock hold
                                </Button>
                            )}
                        </div>
                    </aside>
                </div>
            </section>
        </StorefrontLayout>
    );
}

function ReviewHeader({
    configurationName,
}: {
    configurationName: string;
}) {
    return (
        <section className="border-b bg-muted/20">
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <nav className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Link href="/">
                        Home
                    </Link>

                    <ChevronRight className="size-4" />

                    <span aria-current="page">
                        Review
                    </span>
                </nav>

                <div className="mt-7 flex flex-col justify-between gap-5 md:flex-row md:items-end">
                    <div>
                        <Badge
                            variant="outline"
                            className="rounded-full"
                        >
                            <Check className="size-3" />
                            Configuration complete
                        </Badge>

                        <h1 className="mt-4 text-3xl font-semibold tracking-[-0.03em] sm:text-4xl">
                            Review{' '}
                            {configurationName}
                        </h1>

                        <p className="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground">
                            Confirm the selected
                            components and provide the
                            details required to create
                            the order.
                        </p>
                    </div>

                    <ol className="flex items-center gap-2 text-xs">
                        <Step
                            number="1"
                            label="Configure"
                            complete
                        />

                        <span className="h-px w-5 bg-border" />

                        <Step
                            number="2"
                            label="Review"
                            active
                        />

                        <span className="h-px w-5 bg-border" />

                        <Step
                            number="3"
                            label="Payment"
                        />
                    </ol>
                </div>
            </div>
        </section>
    );
}

function SelectedComponents({
    review,
}: Pick<
    ConfigurationReviewPageProps,
    'review'
>) {
    return (
        <section className="rounded-2xl border">
            <div className="p-5 sm:p-6">
                <p className="text-sm font-medium text-muted-foreground">
                    Final build
                </p>

                <h2 className="mt-2 text-2xl font-semibold">
                    Selected components
                </h2>
            </div>

            <div className="divide-y border-t">
                {review.components.map(
                    (component) => (
                        <div
                            key={component.id}
                            className="flex items-start justify-between gap-5 px-5 py-4 sm:px-6"
                        >
                            <div className="min-w-0">
                                <p className="text-xs text-muted-foreground">
                                    {
                                        component.slot_label
                                    }
                                </p>

                                <p className="mt-1 font-medium">
                                    {component.name}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    SKU {component.sku}
                                </p>
                            </div>

                            <div className="shrink-0 text-right">
                                <p className="font-medium">
                                    {formatMoney(
                                        component
                                            .line_total_in_cents,
                                        review.pricing
                                            .currency,
                                    )}
                                </p>

                                {component.quantity
                                    > 1 && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Quantity{' '}
                                        {
                                            component.quantity
                                        }
                                    </p>
                                )}
                            </div>
                        </div>
                    ),
                )}
            </div>
        </section>
    );
}

function ReviewSummary({
    review,
}: Pick<
    ConfigurationReviewPageProps,
    'review'
>) {
    const currency =
        review.pricing.currency;

    return (
        <div className="rounded-2xl border bg-background p-5 shadow-sm">
            <SystemVisual
                name={review.system.name}
                image={review.system.image}
                className="aspect-[16/9] rounded-xl"
            />

            <h2 className="mt-5 font-semibold">
                Order summary
            </h2>

            <div className="mt-5 space-y-3 text-sm">
                <PriceRow
                    label="Configuration"
                    value={formatMoney(
                        review.pricing
                            .configuration_total_in_cents,
                        currency,
                    )}
                />

                <PriceRow
                    label="Shipping"
                    value={
                        review.pricing
                            .shipping_in_cents
                            === 0
                            ? 'Free'
                            : formatMoney(
                                  review.pricing
                                      .shipping_in_cents,
                                  currency,
                              )
                    }
                />

                <Separator />

                <div className="flex items-end justify-between gap-4">
                    <div>
                        <p className="font-medium">
                            Total
                        </p>

                        <p className="mt-1 text-xs text-muted-foreground">
                            {review.pricing
                                .prices_include_tax
                                ? 'Including tax'
                                : 'Excluding tax'}
                        </p>
                    </div>

                    <p className="text-2xl font-semibold">
                        {formatMoney(
                            review.pricing
                                .order_total_in_cents,
                            currency,
                        )}
                    </p>
                </div>
            </div>
        </div>
    );
}

function PriceRow({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">
                {label}
            </span>

            <span className="font-medium">
                {value}
            </span>
        </div>
    );
}

function Step({
    number,
    label,
    active = false,
    complete = false,
}: {
    number: string;
    label: string;
    active?: boolean;
    complete?: boolean;
}) {
    return (
        <li className="flex items-center gap-2">
            <span
                className={
                    active || complete
                        ? 'grid size-7 place-items-center rounded-full bg-foreground text-background'
                        : 'grid size-7 place-items-center rounded-full border bg-background text-muted-foreground'
                }
            >
                {complete ? (
                    <Check className="size-3.5" />
                ) : (
                    number
                )}
            </span>

            <span
                className={
                    active
                        ? 'font-medium'
                        : 'text-muted-foreground'
                }
            >
                {label}
            </span>
        </li>
    );
}