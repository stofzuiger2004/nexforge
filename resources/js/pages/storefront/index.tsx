import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    ShieldCheck,
    SlidersHorizontal,
    Truck,
    Wrench,
    Dot
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { SystemCard } from '@/components/storefront/system-card';
import { SystemVisual } from '@/components/storefront/system-visual';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatMoney } from '@/lib/money';
import type {
    FeaturedSystem,
    HomePageProps,
    SystemComponentSummary,
} from '@/types/storefront';

const processSteps = [
    {
        number: '01',
        title: 'Choose a starting point',
        description:
            'Start with a balanced system designed for your target resolution and performance level.',
    },
    {
        number: '02',
        title: 'Configure the components',
        description:
            'Replace the CPU, graphics card, memory, storage, case, and other components.',
    },
    {
        number: '03',
        title: 'We validate and build it',
        description:
            'Compatibility and stock are checked before the system is assembled and tested.',
    },
];

const trustItems: {
    icon: LucideIcon;
    title: string;
    description: string;
}[] = [
    {
        icon: ShieldCheck,
        title: 'Compatibility checked',
        description: 'Every final configuration is validated before checkout.',
    },
    {
        icon: Wrench,
        title: 'Professionally assembled',
        description: 'Your system is built, inspected, and stress-tested.',
    },
    {
        icon: Truck,
        title: 'Securely delivered',
        description:
            'Careful packaging protects the completed system in transit.',
    },
];

export default function StorefrontIndex({ featuredSystems }: HomePageProps) {
    const heroSystem = featuredSystems.at(0) ?? null;

    return (
        <StorefrontLayout>
            <Head title="Custom gaming PCs built around you" />

            <HeroSection system={heroSystem} />

            <FeaturedSystemsSection systems={featuredSystems} />

            <ProcessSection />

            <TrustSection />

            <CallToActionSection />
        </StorefrontLayout>
    );
}

function HeroSection({ system }: { system: FeaturedSystem | null }) {
    return (
        <section className="overflow-hidden border-b bg-muted/20">
            <div className="mx-auto grid max-w-7xl items-center gap-14 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-[1.02fr_0.98fr] lg:px-8 lg:py-24">
                <div>
                    <Badge variant="outline" className="rounded-full">
                        Custom gaming systems
                    </Badge>

                    <h1 className="mt-6 max-w-3xl text-4xl font-semibold tracking-[-0.035em] text-balance sm:text-5xl lg:text-6xl">
                        Performance, configured around you.
                    </h1>

                    <p className="mt-6 max-w-xl text-lg leading-8 text-muted-foreground">
                        Start with a carefully balanced gaming PC, then choose
                        the components that match your games, budget, and
                        upgrade plans.
                    </p>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Button size="lg" asChild>
                            <Link href="/configure">
                                Configure your PC
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>

                        <Button size="lg" variant="outline" asChild>
                            <a href="#featured-systems">View gaming PCs</a>
                        </Button>
                    </div>

                    <div className="mt-9 flex flex-col w-100 gap-3 text-sm text-muted-foreground sm:flex-row sm:flex-wrap sm:gap-x-7">
                        <TrustPoint>Automatic compatibility checks</TrustPoint>
                        <TrustPoint>Component-level inventory</TrustPoint>
                        <TrustPoint>Built and tested by hand</TrustPoint>
                    </div>
                </div>

                <HeroSystemPanel system={system} />
            </div>
        </section>
    );
}

function TrustPoint({ children }: { children: React.ReactNode }) {
    return (
        <span className="flex items-center gap-2">
            <span className="grid size-5 place-items-center">
                <Check className="size-3 font-bold" />
            </span>

            {children}
        </span>
    );
}

function HeroSystemPanel({ system }: { system: FeaturedSystem | null }) {
    const price = system?.price
        ? formatMoney(system.price.amount_in_cents, system.price.currency)
        : null;

    return (
        <div className="relative">
            <div className="absolute -inset-10 -z-10 rounded-full bg-foreground/[0.04] blur-3xl" />

            <div className="rounded-[2rem] border bg-card shadow-[0_30px_100px_-45px_rgba(0,0,0,0.4)]">
                <SystemVisual
                    name={system?.name ?? 'Custom gaming PC'}
                    image={system?.image ?? null}
                    eager
                    className="rounded-tl-[1.5rem] rounded-tr-[1.5rem] bg-[#EDEAE4]"
                />

                <div className="p-5 sm:p-6">
                    {system ? (
                        <>
                            <div className="flex items-start justify-between gap-5">
                                <div>
                                    <Badge
                                        variant="outline"
                                        className="mb-3 rounded-full font-normal"
                                    >
                                        <Dot data-icon="inline-start" className="!size-5"/>
                                        {system.availability.label}
                                    </Badge>

                                    <h2 className="text-xl font-semibold">
                                        {system.name}
                                    </h2>
                                </div>

                                <div className="shrink-0 text-right">
                                    <p className="text-xs text-muted-foreground">
                                        From
                                    </p>

                                    <p className="mt-1 text-xl font-semibold">
                                        {price ?? 'Price on request'}
                                    </p>
                                </div>
                            </div>

                            <div className="mt-6 grid grid-cols-2 gap-x-6 gap-y-5 border-t pt-6">
                                <HeroSpecification
                                    label="Processor"
                                    component={system.components.processor}
                                />

                                <HeroSpecification
                                    label="Graphics"
                                    component={system.components.graphics_card}
                                />

                                <HeroSpecification
                                    label="Memory"
                                    component={system.components.memory}
                                />

                                <HeroSpecification
                                    label="Storage"
                                    component={system.components.storage}
                                />
                            </div>
                        </>
                    ) : (
                        <div>
                            <p className="text-xl font-semibold">
                                Your next gaming PC
                            </p>

                            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                Featured systems will appear here after they are
                                published in the catalogue.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

function HeroSpecification({
    label,
    component,
}: {
    label: string;
    component: SystemComponentSummary | null;
}) {
    return (
        <div className="min-w-0">
            <p className="text-xs text-muted-foreground">{label}</p>

            <p className="mt-1 truncate text-sm font-medium">
                {component?.name ?? 'To be confirmed'}
            </p>
        </div>
    );
}

function FeaturedSystemsSection({ systems }: { systems: FeaturedSystem[] }) {
    return (
        <section id="featured-systems" className="scroll-mt-24 py-20 sm:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                    <div className="max-w-2xl">
                        <p className="text-sm font-medium text-muted-foreground">
                            Popular starting points
                        </p>

                        <h2 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Choose your performance level
                        </h2>

                        <p className="mt-4 text-base leading-7 text-muted-foreground">
                            Each system is a configurable foundation. Change the
                            components without starting from an empty build.
                        </p>
                    </div>

                    <Button variant="outline" asChild>
                        <Link href="/gaming-pcs">
                            View all systems
                            <ArrowRight className="size-4" />
                        </Link>
                    </Button>
                </div>

                {systems.length > 0 ? (
                    <div className="mt-10 grid gap-6 lg:grid-cols-3">
                        {systems.map((system) => (
                            <SystemCard key={system.id} system={system} />
                        ))}
                    </div>
                ) : (
                    <div className="mt-10 rounded-2xl border border-dashed p-12 text-center">
                        <h3 className="font-semibold">
                            No featured systems yet
                        </h3>

                        <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                            Publish systems and mark them as featured to show
                            them on the homepage.
                        </p>
                    </div>
                )}
            </div>
        </section>
    );
}

function ProcessSection() {
    return (
        <section className="border-y bg-muted/25 py-20 sm:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl">
                    <p className="text-sm font-medium text-muted-foreground">
                        How it works
                    </p>

                    <h2 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">
                        From configuration to completed system
                    </h2>
                </div>

                <div className="mt-12 grid gap-8 lg:grid-cols-3">
                    {processSteps.map((step) => (
                        <article key={step.number} className="border-t pt-6">
                            <p className="font-mono text-sm text-muted-foreground">
                                {step.number}
                            </p>

                            <h3 className="mt-5 text-lg font-semibold">
                                {step.title}
                            </h3>

                            <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                {step.description}
                            </p>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

function TrustSection() {
    return (
        <section className="py-20 sm:py-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid gap-6 md:grid-cols-3">
                    {trustItems.map((item) => {
                        const Icon = item.icon;

                        return (
                            <article
                                key={item.title}
                                className="rounded-2xl border p-6"
                            >
                                <span className="grid size-10 place-items-center rounded-xl bg-muted">
                                    <Icon className="size-5" />
                                </span>

                                <h2 className="mt-5 font-semibold">
                                    {item.title}
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                    {item.description}
                                </p>
                            </article>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function CallToActionSection() {
    return (
        <section className="pb-20 sm:pb-24">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="overflow-hidden rounded-[2rem] bg-foreground px-6 py-12 text-background sm:px-10 lg:flex lg:items-center lg:justify-between lg:px-14 lg:py-14">
                    <div className="max-w-2xl">
                        <div className="flex items-center gap-2 text-sm text-background/70">
                            <SlidersHorizontal className="size-4" />
                            PC configurator
                        </div>

                        <h2 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Start with a solid base. Make it yours.
                        </h2>

                        <p className="mt-4 max-w-xl leading-7 text-background/70">
                            Select your system and adjust every major component
                            while compatibility and availability are checked in
                            the background.
                        </p>
                    </div>

                    <div className="mt-8 flex shrink-0 flex-col gap-3 sm:flex-row lg:mt-0">
                        <Button size="lg" variant="secondary" asChild>
                            <Link href="/configure">
                                Open configurator
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
