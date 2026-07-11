import { Head, Link } from '@inertiajs/react';
import {
    ChevronRight,
    Cpu,
    Menu,
    Monitor,
    ShieldCheck,
    ShoppingCart,
    Wrench,
    Zap,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Sheet,
    SheetContent,
    SheetTrigger,
} from '@/components/ui/sheet';

type FeaturedSystem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    processor: string;
    graphics_card: string;
    memory: string;
    storage: string;
    price_in_cents: number;
    image_path: string | null;
};

type HomePageProps = {
    featuredSystems: FeaturedSystem[];
};

const categories = [
    {
        name: 'Entry Gaming',
        description: 'Affordable systems for 1080p gaming.',
        icon: Monitor,
    },
    {
        name: 'Performance Gaming',
        description: 'Built for high-refresh 1440p gaming.',
        icon: Zap,
    },
    {
        name: 'Enthusiast Gaming',
        description: 'Premium components for 4K performance.',
        icon: Cpu,
    },
];

const benefits = [
    {
        title: 'Compatibility guaranteed',
        description:
            'Every selected component is checked before your order is placed.',
        icon: ShieldCheck,
    },
    {
        title: 'Built and tested',
        description:
            'Your PC is assembled, stress-tested and prepared for gaming.',
        icon: Wrench,
    },
    {
        title: 'Ready to upgrade',
        description:
            'Choose standard components that can grow with your system.',
        icon: Cpu,
    },
];

const currencyFormatter = new Intl.NumberFormat('en-BE', {
    style: 'currency',
    currency: 'EUR',
    maximumFractionDigits: 0,
});

export default function StorefrontIndex({
    featuredSystems,
}: HomePageProps) {
    return (
        <>
            <Head title="Gaming PCs built for you" />

            <div className="min-h-screen bg-background text-foreground">
                <StoreHeader />

                <main>
                    <HeroSection />

                    <section className="border-y bg-muted/30 py-16">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="mb-10 max-w-2xl">
                                <Badge variant="secondary" className="mb-4">
                                    Find your system
                                </Badge>

                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    Gaming PCs for every level
                                </h2>

                                <p className="mt-4 text-muted-foreground">
                                    Start with a carefully selected system or
                                    configure every component yourself.
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-3">
                                {categories.map((category) => {
                                    const Icon = category.icon;

                                    return (
                                        <Card
                                            key={category.name}
                                            className="group transition hover:-translate-y-1 hover:shadow-lg"
                                        >
                                            <CardHeader>
                                                <div className="mb-4 flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                    <Icon className="size-6" />
                                                </div>

                                                <CardTitle>
                                                    {category.name}
                                                </CardTitle>

                                                <CardDescription>
                                                    {category.description}
                                                </CardDescription>
                                            </CardHeader>

                                            <CardFooter>
                                                <Button
                                                    variant="ghost"
                                                    className="px-0"
                                                    asChild
                                                >
                                                    <Link href="/gaming-pcs">
                                                        View systems
                                                        <ChevronRight className="ml-1 size-4 transition-transform group-hover:translate-x-1" />
                                                    </Link>
                                                </Button>
                                            </CardFooter>
                                        </Card>
                                    );
                                })}
                            </div>
                        </div>
                    </section>

                    <section className="py-20">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                                <div>
                                    <Badge variant="outline" className="mb-4">
                                        Popular configurations
                                    </Badge>

                                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                        Featured gaming PCs
                                    </h2>
                                </div>

                                <Button variant="outline" asChild>
                                    <Link href="/gaming-pcs">
                                        View all systems
                                    </Link>
                                </Button>
                            </div>
                            {featuredSystems.length > 0 ? (
                                <div className="grid gap-6 lg:grid-cols-3">
                                {featuredSystems.map((system) => (
                                    <Card
                                        key={system.id}
                                        className="overflow-hidden"
                                    >
                                        <div className="flex aspect-[16/10] items-center justify-center bg-gradient-to-br from-muted to-muted/40">
                                            <div className="relative flex size-36 items-center justify-center rounded-2xl border bg-background shadow-xl">
                                                <Cpu className="size-16 text-primary" />
                                                <div className="absolute inset-3 rounded-xl border border-primary/20" />
                                            </div>
                                        </div>

                                        <CardHeader>
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <CardTitle>
                                                        {system.name}
                                                    </CardTitle>

                                                    <CardDescription className="mt-2">
                                                        {system.description}
                                                    </CardDescription>
                                                </div>

                                                <Badge>Featured</Badge>
                                            </div>
                                        </CardHeader>

                                        <CardContent>
                                            <dl className="space-y-3 text-sm">
                                                <div className="flex justify-between gap-4">
                                                    <dt className="text-muted-foreground">
                                                        Processor
                                                    </dt>
                                                    <dd className="text-right font-medium">
                                                        {system.processor}
                                                    </dd>
                                                </div>

                                                <div className="flex justify-between gap-4">
                                                    <dt className="text-muted-foreground">
                                                        Graphics
                                                    </dt>
                                                    <dd className="text-right font-medium">
                                                        {system.graphics_card}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </CardContent>

                                        <CardFooter className="flex items-center justify-between border-t bg-muted/20 pt-6">
                                            <div>
                                                <p className="text-xs text-muted-foreground">
                                                    Starting at
                                                </p>
                                                <p className="text-2xl font-bold">
                                                    {currencyFormatter.format(system.price_in_cents/100)}
                                                </p>
                                            </div>

                                            <Button asChild>
                                                <Link
                                                    href={`/gaming-pcs/${system.slug}`}
                                                >
                                                    Configure
                                                </Link>
                                            </Button>
                                        </CardFooter>
                                    </Card>
                                ))}
                            </div>
                            ) : (
                                <Card>
                                    <CardContent className="py-12 text-center">
                                        <h3 className="font-semibold">
                                            No featured systems available
                                        </h3>

                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Featured gaming PCs will appear here once they are added.
                                        </p>
                                    </CardContent>
                                </Card>
                            )}
                            
                        </div>
                    </section>

                    <section className="bg-primary py-20 text-primary-foreground">
                        <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                            <div>
                                <Badge
                                    variant="secondary"
                                    className="mb-5"
                                >
                                    PC configurator
                                </Badge>

                                <h2 className="text-3xl font-bold tracking-tight sm:text-5xl">
                                    Build the PC you actually want
                                </h2>

                                <p className="mt-5 max-w-xl text-lg text-primary-foreground/75">
                                    Select your processor, graphics card,
                                    memory, storage and case. We will guide you
                                    through compatible options.
                                </p>

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    <Button
                                        size="lg"
                                        variant="secondary"
                                        asChild
                                    >
                                        <Link href="/configure">
                                            Start configuring
                                            <ChevronRight className="ml-2 size-4" />
                                        </Link>
                                    </Button>

                                    <Button
                                        size="lg"
                                        variant="outline"
                                        className="border-primary-foreground/25 bg-transparent text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground"
                                        asChild
                                    >
                                        <Link href="/how-it-works">
                                            How it works
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                                {benefits.map((benefit) => {
                                    const Icon = benefit.icon;

                                    return (
                                        <div
                                            key={benefit.title}
                                            className="flex gap-4 rounded-2xl border border-primary-foreground/15 bg-primary-foreground/5 p-5"
                                        >
                                            <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary-foreground/10">
                                                <Icon className="size-5" />
                                            </div>

                                            <div>
                                                <h3 className="font-semibold">
                                                    {benefit.title}
                                                </h3>
                                                <p className="mt-1 text-sm text-primary-foreground/70">
                                                    {benefit.description}
                                                </p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </section>
                </main>

                <StoreFooter />
            </div>
        </>
    );
}

function StoreHeader() {
    return (
        <header className="sticky top-0 z-50 border-b bg-background/90 backdrop-blur">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" className="flex items-center gap-2">
                    <div className="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <Zap className="size-5" />
                    </div>

                    <span className="text-lg font-bold tracking-tight">
                        ForgePC
                    </span>
                </Link>

                <nav className="hidden items-center gap-8 md:flex">
                    <Link
                        href="/gaming-pcs"
                        className="text-sm font-medium text-muted-foreground transition hover:text-foreground"
                    >
                        Gaming PCs
                    </Link>
                    <Link
                        href="/configure"
                        className="text-sm font-medium text-muted-foreground transition hover:text-foreground"
                    >
                        Configure
                    </Link>
                    <Link
                        href="/components"
                        className="text-sm font-medium text-muted-foreground transition hover:text-foreground"
                    >
                        Components
                    </Link>
                    <Link
                        href="/support"
                        className="text-sm font-medium text-muted-foreground transition hover:text-foreground"
                    >
                        Support
                    </Link>
                </nav>

                <div className="hidden items-center gap-2 md:flex">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/cart" aria-label="Shopping cart">
                            <ShoppingCart className="size-5" />
                        </Link>
                    </Button>

                    <Button variant="ghost" asChild>
                        <Link href="/login">Log in</Link>
                    </Button>

                    <Button asChild>
                        <Link href="/register">Create account</Link>
                    </Button>
                </div>

                <Sheet>
                    <SheetTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="md:hidden"
                            aria-label="Open navigation"
                        >
                            <Menu className="size-5" />
                        </Button>
                    </SheetTrigger>

                    <SheetContent>
                        <nav className="mt-8 flex flex-col gap-5">
                            <Link href="/gaming-pcs">Gaming PCs</Link>
                            <Link href="/configure">Configure</Link>
                            <Link href="/components">Components</Link>
                            <Link href="/support">Support</Link>
                            <Link href="/cart">Shopping cart</Link>
                            <Link href="/login">Log in</Link>

                            <Button asChild>
                                <Link href="/register">Create account</Link>
                            </Button>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
    );
}

function HeroSection() {
    return (
        <section className="relative overflow-hidden">
            <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,hsl(var(--primary)/0.16),transparent_35%)]" />

            <div className="mx-auto grid min-h-[680px] max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <Badge variant="secondary" className="mb-5">
                        Built for gaming
                    </Badge>

                    <h1 className="max-w-3xl text-5xl font-bold tracking-tight sm:text-6xl lg:text-7xl">
                        Your game.
                        <span className="block text-primary">Your machine.</span>
                    </h1>

                    <p className="mt-6 max-w-xl text-lg leading-8 text-muted-foreground">
                        Choose a professionally designed gaming PC or configure
                        every component yourself. Compatibility and performance
                        are checked automatically.
                    </p>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Button size="lg" asChild>
                            <Link href="/configure">
                                Build your PC
                                <ChevronRight className="ml-2 size-4" />
                            </Link>
                        </Button>

                        <Button size="lg" variant="outline" asChild>
                            <Link href="/gaming-pcs">
                                Browse gaming PCs
                            </Link>
                        </Button>
                    </div>

                    <div className="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-sm text-muted-foreground">
                        <span className="flex items-center gap-2">
                            <ShieldCheck className="size-4 text-primary" />
                            Compatibility checked
                        </span>
                        <span className="flex items-center gap-2">
                            <Wrench className="size-4 text-primary" />
                            Professionally assembled
                        </span>
                    </div>
                </div>

                <div className="relative">
                    <div className="absolute -inset-12 -z-10 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative mx-auto aspect-square max-w-lg rounded-[2rem] border bg-card p-8 shadow-2xl">
                        <div className="absolute left-8 top-8 flex gap-2">
                            <span className="size-3 rounded-full bg-muted-foreground/25" />
                            <span className="size-3 rounded-full bg-muted-foreground/25" />
                            <span className="size-3 rounded-full bg-muted-foreground/25" />
                        </div>

                        <div className="flex h-full items-center justify-center">
                            <div className="relative flex h-80 w-56 items-center justify-center rounded-3xl border-2 bg-background shadow-xl">
                                <div className="absolute inset-5 rounded-2xl border border-primary/30" />
                                <div className="absolute left-8 top-10 size-24 rounded-full border-4 border-primary/50 shadow-[0_0_40px_hsl(var(--primary)/0.35)]" />
                                <Cpu className="size-20 text-primary" />
                                <div className="absolute bottom-10 h-3 w-28 rounded-full bg-primary/30" />
                            </div>
                        </div>

                        <div className="absolute bottom-8 right-8 rounded-xl border bg-background/90 p-4 shadow-lg backdrop-blur">
                            <p className="text-xs text-muted-foreground">
                                Estimated performance
                            </p>
                            <p className="mt-1 font-semibold">
                                1440p · Ultra settings
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

function StoreFooter() {
    return (
        <footer className="border-t">
            <div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-10 text-sm text-muted-foreground sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                <div>
                    <p className="font-semibold text-foreground">ForgePC</p>
                    <p className="mt-1">
                        Custom gaming systems built for performance.
                    </p>
                </div>

                <div className="flex flex-wrap gap-6">
                    <Link href="/terms" className="hover:text-foreground">
                        Terms
                    </Link>
                    <Link href="/privacy" className="hover:text-foreground">
                        Privacy
                    </Link>
                    <Link href="/contact" className="hover:text-foreground">
                        Contact
                    </Link>
                </div>
            </div>
        </footer>
    );
}