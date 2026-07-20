import { Link, usePage } from '@inertiajs/react';
import { Menu, UserRound } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { StorefrontSharedProps } from '@/types/storefront';

const navigation = [
    {
        label: 'Configurator',
        href: '/configure',
    },
    {
        label: 'Components',
        href: '/components',
    },
    {
        label: 'Support',
        href: '/support',
    },
];
function isNavigationActive(currentPath: string, href: string): boolean {
    if (href === '/gaming-pcs') {
        return (
            currentPath === '/gaming-pcs' ||
            currentPath.startsWith('/gaming-pcs/')
        );
    }

    if (href === '/configure') {
        return (
            currentPath === '/configure' ||
            currentPath.startsWith('/configure/')
        );
    }

    return currentPath === href || currentPath.startsWith(`${href}/`);
}

export function SiteHeader() {
    const page = usePage<StorefrontSharedProps>();

    const { auth } = page.props;
    const user = auth?.user ?? null;

    const currentPath = page.url.split('?')[0];

    return (
        <header className="sticky top-0 z-50 border-b bg-background/90 backdrop-blur-xl">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Brand />

                <nav className="hidden items-center gap-7 lg:flex">
                    {navigation.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={[
                                'text-sm font-medium transition-colors hover:text-foreground',
                                isNavigationActive(currentPath, item.href)
                                    ? 'text-foreground'
                                    : 'text-muted-foreground',
                            ].join(' ')}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                <div className="hidden items-center gap-2 lg:flex">
                    {user ? (
                        <Button variant="ghost" asChild>
                            <Link href="/admin">
                                <UserRound className="size-4" />
                                Account
                            </Link>
                        </Button>
                    ) : (
                        <Button variant="ghost" asChild>
                            <Link href="/login">Log in</Link>
                        </Button>
                    )}

                    <Button asChild>
                        <Link href="/configure">Configure a PC</Link>
                    </Button>
                </div>

                <Sheet>
                    <SheetTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            aria-label="Open navigation"
                        >
                            <Menu className="size-5" />
                        </Button>
                    </SheetTrigger>

                    <SheetContent
                        side="right"
                        className="flex w-[320px] flex-col p-0 sm:w-[380px]"
                    >
                        <SheetHeader className="border-b p-6 text-left">
                            <SheetTitle>Navigation</SheetTitle>
                        </SheetHeader>

                        <nav className="flex flex-1 flex-col gap-1 p-4">
                            {navigation.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={[
                                        'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                        isNavigationActive(
                                            currentPath,
                                            item.href,
                                        )
                                            ? 'bg-muted text-foreground'
                                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                    ].join(' ')}
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>

                        <div className="space-y-2 border-t p-4">
                            {user ? (
                                <Button
                                    variant="outline"
                                    className="w-full"
                                    asChild
                                >
                                    <Link href="/dashboard">
                                        <UserRound className="size-4" />
                                        Account
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        asChild
                                    >
                                        <Link href="/login">Log in</Link>
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        className="w-full"
                                        asChild
                                    >
                                        <Link href="/register">
                                            Create account
                                        </Link>
                                    </Button>
                                </>
                            )}

                            <Button className="w-full" asChild>
                                <Link href="/configure">Configure a PC</Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
    );
}

function Brand() {
    return (
        <Link
            href="/"
            className="flex items-center gap-3"
            aria-label="NexForge homepage"
        >
            <AppLogoIcon className="size-9"/>

            <span className="text-lg font-semibold tracking-tight">
                NexForge
            </span>
        </Link>
    );
}
