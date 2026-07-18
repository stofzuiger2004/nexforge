import {
    Head,
    Link,
} from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    Cpu,
    Gauge,
    PackageCheck,
    Settings2,
    ShieldCheck,
    Sparkles,
    Wrench,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { SystemCard } from '@/components/storefront/system-card';
import { Button } from '@/components/ui/button';
import StorefrontLayout from '@/layouts/storefront-layout';
import type {
    FeaturedSystem,
    SystemIndexPageProps,
} from '@/types/storefront';

const benefits: {
    icon: LucideIcon;
    title: string;
    description: string;
}[] = [
    {
        icon: Settings2,
        title: 'Fully configurable',
        description:
            'Replace every major component while keeping a proven base configuration.',
    },
    {
        icon: ShieldCheck,
        title: 'Compatibility validated',
        description:
            'Your final component selection is checked before checkout.',
    },
    {
        icon: PackageCheck,
        title: 'Live component stock',
        description:
            'Availability is calculated from the parts required for the full system.',
    },
];

const processSteps = [
    {
        number: '01',
        title: 'Choose a preset',
        description:
            'Select the base system that best matches your target resolution, games, and budget.',
    },
    {
        number: '02',
        title: 'Adjust the components',
        description:
            'Upgrade or downgrade the CPU, graphics card, memory, storage, cooling, case, and more.',
    },
    {
        number: '03',
        title: 'Validate your build',
        description:
            'NexForge verifies compatibility, pricing, and complete-build availability before checkout.',
    },
];

export default function SystemIndex({
    systems,
    page,
}: SystemIndexPageProps) {
    return (
        <StorefrontLayout>
            <Head title={page.title} />

            <main>
                <PageHero
                    eyebrow={page.eyebrow}
                    title={page.title}
                    description={page.description}
                    context={page.context}
                    systemCount={systems.length}
                />

                <BenefitsSection />

                <SystemsSection
                    systems={systems}
                    context={page.context}
                />

                <ProcessSection />

                <BottomCallToAction
                    context={page.context}
                />
            </main>
        </StorefrontLayout>
    );
}

type PageHeroProps = {
    eyebrow: string;
    title: string;
    description: string;
    context: SystemIndexPageProps['page']['context'];
    systemCount: number;
};

function PageHero({
    eyebrow,
    title,
    description,
    context,
    systemCount,
}: PageHeroProps) {
    return (
        <section className="border-b">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                <div className="grid items-end gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="max-w-3xl">
                        <div className="mb-5 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <Sparkles className="size-4" />
                            {eyebrow}
                        </div>

                        <h1 className="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                            {title}
                        </h1>

                        <p className="mt-6 max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg sm:leading-8">
                            {description}
                        </p>

                        <div className="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-muted-foreground">
                            <HeroPoint>
                                Server-authoritative pricing
                            </HeroPoint>

                            <HeroPoint>
                                Automatic compatibility checks
                            </HeroPoint>

                            <HeroPoint>
                                Complete-build availability
                            </HeroPoint>
                        </div>
                    </div>

                    <div className="rounded-2xl border bg-muted/20 p-6">
                        <div className="flex items-start gap-4">
                            <span className="grid size-11 shrink-0 place-items-center rounded-xl border bg-background">
                                {context ===
                                'configurator' ? (
                                    <Settings2 className="size-5" />
                                ) : (
                                    <Cpu className="size-5" />
                                )}
                            </span>

                            <div>
                                <p className="text-3xl font-semibold tracking-tight">
                                    {systemCount}
                                </p>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    {systemCount === 1
                                        ? 'configurable preset available'
                                        : 'configurable presets available'}
                                </p>
                            </div>
                        </div>

                        <div className="mt-6 border-t pt-5">
                            <p className="text-sm leading-6 text-muted-foreground">
                                Every preset includes a complete
                                base component list. You can review
                                the system first or start configuring
                                immediately.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function HeroPoint({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <span className="inline-flex items-center gap-2">
            <Check className="size-4 text-foreground" />
            {children}
        </span>
    );
}

function BenefitsSection() {
    return (
        <section className="border-b bg-muted/10">
            <div className="mx-auto grid max-w-7xl gap-px px-4 py-8 sm:px-6 md:grid-cols-3 lg:px-8">
                {benefits.map((benefit) => {
                    const Icon = benefit.icon;

                    return (
                        <div
                            key={benefit.title}
                            className="flex gap-4 px-0 py-5 md:px-6 md:first:pl-0 md:last:pr-0"
                        >
                            <span className="grid size-10 shrink-0 place-items-center rounded-xl border bg-background">
                                <Icon className="size-4" />
                            </span>

                            <div>
                                <h2 className="font-medium">
                                    {benefit.title}
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                    {benefit.description}
                                </p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

function SystemsSection({
    systems,
    context,
}: {
    systems: FeaturedSystem[];
    context: SystemIndexPageProps['page']['context'];
}) {
    return (
        <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <div className="mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">
                        Starting configurations
                    </p>

                    <h2 className="mt-2 text-3xl font-semibold tracking-tight">
                        Select your performance level
                    </h2>

                    <p className="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
                        {context === 'configurator'
                            ? 'Choose a foundation and continue directly to component selection.'
                            : 'Compare each base system, review its core specifications, or configure it immediately.'}
                    </p>
                </div>

                {context === 'catalogue' && (
                    <Button
                        variant="outline"
                        asChild
                    >
                        <Link href="/configure">
                            Open configurator
                            <ArrowRight className="size-4" />
                        </Link>
                    </Button>
                )}
            </div>

            {systems.length > 0 ? (
                <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    {systems.map((system) => (
                        <SystemCard
                            key={system.id}
                            system={system}
                        />
                    ))}
                </div>
            ) : (
                <EmptyState />
            )}
        </section>
    );
}

function EmptyState() {
    return (
        <div className="rounded-2xl border border-dashed bg-muted/10 px-6 py-16 text-center">
            <span className="mx-auto grid size-12 place-items-center rounded-xl border bg-background">
                <Cpu className="size-5" />
            </span>

            <h2 className="mt-5 text-xl font-semibold">
                No configurable systems are available
            </h2>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                Published systems with an active storefront
                price will appear here automatically.
            </p>

            <Button
                asChild
                variant="outline"
                className="mt-6"
            >
                <Link href="/">
                    Return to homepage
                </Link>
            </Button>
        </div>
    );
}

function ProcessSection() {
    return (
        <section className="border-y bg-muted/20">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <div className="max-w-2xl">
                    <p className="text-sm font-medium text-muted-foreground">
                        How it works
                    </p>

                    <h2 className="mt-2 text-3xl font-semibold tracking-tight">
                        From preset to your own gaming PC
                    </h2>
                </div>

                <div className="mt-10 grid gap-5 md:grid-cols-3">
                    {processSteps.map((step) => (
                        <article
                            key={step.number}
                            className="rounded-2xl border bg-background p-6"
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-mono text-sm text-muted-foreground">
                                    {step.number}
                                </span>

                                <Gauge className="size-4 text-muted-foreground" />
                            </div>

                            <h3 className="mt-8 text-lg font-semibold">
                                {step.title}
                            </h3>

                            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                {step.description}
                            </p>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

function BottomCallToAction({
    context,
}: {
    context: SystemIndexPageProps['page']['context'];
}) {
    return (
        <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <div className="overflow-hidden rounded-3xl border bg-foreground text-background">
                <div className="grid gap-10 px-6 py-10 sm:px-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:px-12 lg:py-12">
                    <div>
                        <div className="flex items-center gap-2 text-sm text-background/70">
                            <Wrench className="size-4" />
                            Built and tested by NexForge
                        </div>

                        <h2 className="mt-4 max-w-2xl text-3xl font-semibold tracking-tight">
                            Start with a proven system.
                            Finish with a PC built around you.
                        </h2>

                        <p className="mt-4 max-w-2xl text-sm leading-6 text-background/70 sm:text-base">
                            Every selection remains subject to
                            server-side price, compatibility, and
                            inventory validation.
                        </p>
                    </div>

                    {context === 'catalogue' ? (
                        <Button
                            asChild
                            variant="secondary"
                            className="w-full lg:w-auto"
                        >
                            <Link href="/configure">
                                Start configuring
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            asChild
                            variant="secondary"
                            className="w-full lg:w-auto"
                        >
                            <Link href="/gaming-pcs">
                                Compare systems
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </section>
    );
}