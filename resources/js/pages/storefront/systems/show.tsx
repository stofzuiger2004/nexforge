import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    ChevronRight,
    ShieldCheck,
    Truck,
    Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';

import { SystemComponentList } from '@/components/storefront/system-component-list';
import { SystemImageGallery } from '@/components/storefront/system-image-gallery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatMoney } from '@/lib/money';
import type {
    SystemDetail,
    SystemDetailComponent,
    SystemDetailPageProps,
} from '@/types/storefront';

export default function SystemShow({
    system,
}: SystemDetailPageProps) {
    const configuratorUrl =
        `/configure?system=${encodeURIComponent(system.slug)}`;

    return (
        <StorefrontLayout>
            <Head
                title={`${system.name} gaming PC`}
            />

            <SystemHero
                system={system}
                configuratorUrl={
                    configuratorUrl
                }
            />

            <SystemDetails system={system} />

            <BottomCallToAction
                system={system}
                configuratorUrl={
                    configuratorUrl
                }
            />
        </StorefrontLayout>
    );
}

type SystemHeroProps = {
    system: SystemDetail;
    configuratorUrl: string;
};

function SystemHero({
    system,
    configuratorUrl,
}: SystemHeroProps) {
    const price = system.price
        ? formatMoney(
              system.price.amount_in_cents,
              system.price.currency,
          )
        : null;

    const compareAtPrice =
        system.price
            ?.compare_at_amount_in_cents
        ? formatMoney(
              system.price
                  .compare_at_amount_in_cents,
              system.price.currency,
          )
        : null;

    const processor = componentForSlot(
        system,
        'cpu',
    );

    const graphicsCard = componentForSlot(
        system,
        'graphics_card',
    );

    const memory = componentForSlot(
        system,
        'memory',
    );

    const storage = componentForSlot(
        system,
        'primary_storage',
    );

    return (
        <section className="border-b bg-muted/20">
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <Breadcrumbs
                    systemName={system.name}
                />

                <div className="mt-8 grid items-start gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.9fr)]">
                    <SystemImageGallery
                        key={system.id}
                        name={system.name}
                        images={system.images}
                    />

                    <div className="lg:pt-4">
                        <Badge
                            variant="outline"
                            className="rounded-full font-normal"
                        >
                            {
                                system
                                    .availability
                                    .label
                            }
                        </Badge>

                        <h1 className="mt-5 text-balance text-4xl font-semibold tracking-[-0.035em] sm:text-5xl">
                            {system.name}
                        </h1>

                        <p className="mt-2 text-sm text-muted-foreground">
                            System reference{' '}
                            {system.sku}
                        </p>

                        <p className="mt-6 max-w-xl text-base leading-7 text-muted-foreground">
                            {system.description
                                ?? system.short_description
                                ?? 'A configurable gaming system built from carefully selected components.'}
                        </p>

                        <div className="mt-8 grid grid-cols-2 gap-x-8 gap-y-5 border-y py-6">
                            <KeySpecification
                                label="Processor"
                                component={
                                    processor
                                }
                            />

                            <KeySpecification
                                label="Graphics"
                                component={
                                    graphicsCard
                                }
                            />

                            <KeySpecification
                                label="Memory"
                                component={
                                    memory
                                }
                            />

                            <KeySpecification
                                label="Storage"
                                component={
                                    storage
                                }
                            />
                        </div>

                        <div className="mt-8">
                            <p className="text-sm text-muted-foreground">
                                Starting configuration
                            </p>

                            <div className="mt-1 flex flex-wrap items-baseline gap-3">
                                <p className="text-3xl font-semibold tracking-tight">
                                    {price
                                        ?? 'Price on request'}
                                </p>

                                {compareAtPrice && (
                                    <p className="text-base text-muted-foreground line-through">
                                        {
                                            compareAtPrice
                                        }
                                    </p>
                                )}
                            </div>

                            <AvailabilityDescription
                                system={system}
                            />
                        </div>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            {system.is_configurable ? (
                                <Button
                                    size="lg"
                                    asChild
                                >
                                    <Link
                                        href={
                                            configuratorUrl
                                        }
                                    >
                                        Configure this
                                        system

                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    size="lg"
                                    disabled
                                >
                                    Configuration
                                    unavailable
                                </Button>
                            )}

                            <Button
                                size="lg"
                                variant="outline"
                                asChild
                            >
                                <Link href="/gaming-pcs">
                                    <ArrowLeft className="size-4" />
                                    All gaming PCs
                                </Link>
                            </Button>
                        </div>

                        <p className="mt-4 max-w-lg text-xs leading-5 text-muted-foreground">
                            Final price and stock are
                            recalculated after you change
                            components in the
                            configurator.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

function Breadcrumbs({
    systemName,
}: {
    systemName: string;
}) {
    return (
        <nav
            aria-label="Breadcrumb"
            className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            <Link
                href="/"
                className="transition-colors hover:text-foreground"
            >
                Home
            </Link>

            <ChevronRight className="size-4" />

            <Link
                href="/gaming-pcs"
                className="transition-colors hover:text-foreground"
            >
                Gaming PCs
            </Link>

            <ChevronRight className="size-4" />

            <span
                className="max-w-[18rem] truncate text-foreground"
                aria-current="page"
            >
                {systemName}
            </span>
        </nav>
    );
}

type KeySpecificationProps = {
    label: string;
    component: SystemDetailComponent | null;
};

function KeySpecification({
    label,
    component,
}: KeySpecificationProps) {
    return (
        <div className="min-w-0">
            <p className="text-xs text-muted-foreground">
                {label}
            </p>

            <p className="mt-1 truncate text-sm font-medium">
                {component?.name
                    ?? 'To be confirmed'}
            </p>
        </div>
    );
}

function AvailabilityDescription({
    system,
}: {
    system: SystemDetail;
}) {
    const availableBuilds =
        system.availability.available_builds;

    let message: string;

    if (availableBuilds === null) {
        message =
            'This system is assembled after your configuration is confirmed.';
    } else if (availableBuilds === 0) {
        message =
            'One or more starting components are currently unavailable. You may still configure alternatives.';
    } else if (availableBuilds === 1) {
        message =
            'Components for one complete starting configuration are currently available.';
    } else {
        message = `Components for ${availableBuilds} complete starting configurations are currently available.`;
    }

    return (
        <p className="mt-3 max-w-lg text-sm leading-6 text-muted-foreground">
            {message}
        </p>
    );
}

function SystemDetails({
    system,
}: {
    system: SystemDetail;
}) {
    return (
        <section className="py-16 sm:py-20">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-8">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">
                        Starting configuration
                    </p>

                    <h2 className="mt-3 text-3xl font-semibold tracking-tight">
                        Components included
                    </h2>

                    <p className="mt-4 max-w-2xl leading-7 text-muted-foreground">
                        This is the base configuration
                        used to calculate the starting
                        price. Components marked as
                        changeable will be selectable in
                        the configurator.
                    </p>

                    <div className="mt-8">
                        <SystemComponentList
                            components={
                                system.components
                            }
                        />
                    </div>
                </div>

                <aside>
                    <div className="sticky top-24 rounded-2xl border p-6">
                        <h2 className="font-semibold">
                            What happens next
                        </h2>

                        <div className="mt-6 space-y-6">
                            <ProcessItem
                                icon={
                                    ShieldCheck
                                }
                                title="Compatibility validation"
                            >
                                Every component change is
                                checked against the rest
                                of the build.
                            </ProcessItem>

                            <ProcessItem
                                icon={Check}
                                title="Live stock check"
                            >
                                Component availability is
                                checked before the
                                configuration can proceed.
                            </ProcessItem>

                            <ProcessItem
                                icon={Wrench}
                                title="Assembly and testing"
                            >
                                The finished PC is
                                assembled, inspected, and
                                stress-tested.
                            </ProcessItem>

                            <ProcessItem
                                icon={Truck}
                                title="Protected delivery"
                            >
                                The completed system is
                                packaged for safe
                                transport.
                            </ProcessItem>
                        </div>

                        <div className="mt-7 border-t pt-6">
                            <p className="text-sm font-medium">
                                No commitment yet
                            </p>

                            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                Opening the configurator
                                does not reserve stock or
                                place an order.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    );
}

type ProcessItemProps = {
    icon: typeof ShieldCheck;
    title: string;
    children: ReactNode;
};

function ProcessItem({
    icon: Icon,
    title,
    children,
}: ProcessItemProps) {
    return (
        <div className="flex gap-4">
            <span className="grid size-9 shrink-0 place-items-center rounded-xl bg-muted">
                <Icon className="size-4" />
            </span>

            <div>
                <h3 className="text-sm font-medium">
                    {title}
                </h3>

                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                    {children}
                </p>
            </div>
        </div>
    );
}

type BottomCallToActionProps = {
    system: SystemDetail;
    configuratorUrl: string;
};

function BottomCallToAction({
    system,
    configuratorUrl,
}: BottomCallToActionProps) {
    return (
        <section className="pb-20 sm:pb-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="rounded-[2rem] bg-foreground px-6 py-12 text-background sm:px-10 lg:flex lg:items-center lg:justify-between lg:px-14">
                    <div className="max-w-2xl">
                        <p className="text-sm text-background/65">
                            Ready to personalize it?
                        </p>

                        <h2 className="mt-3 text-3xl font-semibold tracking-tight">
                            Configure the{' '}
                            {system.name}
                        </h2>

                        <p className="mt-4 max-w-xl leading-7 text-background/70">
                            Use this build as your
                            starting point and adjust the
                            major components to match
                            your performance target.
                        </p>
                    </div>

                    <div className="mt-8 shrink-0 lg:mt-0">
                        {system.is_configurable ? (
                            <Button
                                size="lg"
                                variant="secondary"
                                asChild
                            >
                                <Link
                                    href={
                                        configuratorUrl
                                    }
                                >
                                    Open configurator

                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        ) : (
                            <Button
                                size="lg"
                                variant="secondary"
                                disabled
                            >
                                Configuration unavailable
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

function componentForSlot(
    system: SystemDetail,
    slot: string,
): SystemDetailComponent | null {
    return (
        system.components.find(
            (component) =>
                component.slot === slot,
        ) ?? null
    );
}