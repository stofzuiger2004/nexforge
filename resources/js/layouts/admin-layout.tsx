import { Link, usePage } from '@inertiajs/react';
import {
    ExternalLink,
    LayoutDashboard,
    LogOut,
    Menu,
    PackageSearch,
    ShoppingBag,
    Boxes
} from 'lucide-react';
import type { PropsWithChildren, ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type { AdminSharedProps } from '@/types/admin';

type AdminLayoutProps = PropsWithChildren<{
    title: string;
    description?: string;
    actions?: ReactNode;
}>;
type AdminNavigationProps = {
    currentUrl: string;
    items: NavigationItem[];
};
type NavigationPermission =
    keyof AdminSharedProps['auth']['can'];

type NavigationItem = {
    label: string;
    href: string;
    icon: typeof LayoutDashboard;
    permission?: NavigationPermission;
    isActive: (url: string) => boolean;
};
const navigation: NavigationItem[] = [
    {
        label: 'Dashboard',
        href: '/admin',
        icon: LayoutDashboard,
        isActive: (url: string) =>
            url === '/admin',
    },
    {
        label: 'Orders',
        href: '/admin/orders',
        icon: ShoppingBag,
        permission: 'viewOrders',
        isActive: (url: string) =>
            url.startsWith('/admin/orders'),
    },
    {
        label: 'Inventory',
        href: '/admin/inventory',
        icon: Boxes,
        permission: 'viewInventory',
        isActive: (url: string) =>
            url.startsWith('/admin/inventory'),
    },
];

export default function AdminLayout({
    title,
    description,
    actions,
    children,
}: AdminLayoutProps) {
    const page = usePage<AdminSharedProps>();
    const visibleNavigation = navigation.filter(
    (item) =>
        item.permission === undefined ||
        page.props.auth.can[item.permission],
);
    const user = page.props.auth.user;

    return (
        <div className="min-h-screen bg-muted/20">
            <aside className="fixed inset-y-0 left-0 z-40 hidden w-64 border-r bg-background lg:flex lg:flex-col">
                <AdminBrand />

                <AdminNavigation currentUrl={page.url} items={visibleNavigation} />

                <div className="mt-auto border-t p-4">
                    <p className="truncate text-sm font-medium">{user?.name}</p>

                    <p className="mt-1 truncate text-xs text-muted-foreground">
                        {user?.email}
                    </p>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="mt-4 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted hover:text-foreground"
                    >
                        <LogOut className="size-4" />
                        Log out
                    </Link>
                </div>
            </aside>

            <div className="lg:pl-64">
                <header className="sticky top-0 z-30 border-b bg-background/90 backdrop-blur-xl">
                    <div className="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-3">
                            <MobileNavigation currentUrl={page.url} />

                            <p className="text-sm font-medium">
                                Administration
                            </p>
                        </div>

                        <Button variant="outline" size="sm" asChild>
                            <Link href="/">
                                Storefront
                                <ExternalLink className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </header>

                <main>
                    <div className="border-b bg-background">
                        <div className="mx-auto flex max-w-[1600px] flex-col justify-between gap-5 px-4 py-8 sm:px-6 md:flex-row md:items-end lg:px-8">
                            <div>
                                <h1 className="text-3xl font-semibold tracking-tight">
                                    {title}
                                </h1>

                                {description && (
                                    <p className="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">
                                        {description}
                                    </p>
                                )}
                            </div>

                            {actions}
                        </div>
                    </div>

                    <div className="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 lg:px-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}

function AdminBrand() {
    return (
        <Link
            href="/admin"
            className="flex h-16 items-center gap-3 border-b px-5"
        >
            <span className="grid size-9 place-items-center rounded-xl bg-foreground text-background">
                <PackageSearch className="size-5" />
            </span>

            <div>
                <p className="font-semibold">NexForge</p>

                <p className="text-xs text-muted-foreground">Admin</p>
            </div>
        </Link>
    );
}

function AdminNavigation({ currentUrl, items }: AdminNavigationProps) {
    return (
        <nav className="space-y-1 p-4">
            {items.map((item) => {
                const Icon = item.icon;
                const active = item.isActive(currentUrl);

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition',

                            active
                                ? 'bg-foreground text-background'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        )}
                    >
                        <Icon className="size-4" />
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}

function MobileNavigation({ currentUrl }: { currentUrl: string }) {
    const page = usePage<AdminSharedProps>();
    const visibleNavigation = navigation.filter(
    (item) =>
        item.permission === undefined ||
        page.props.auth.can[item.permission],
);
    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="lg:hidden"
                    aria-label="Open admin navigation"
                >
                    <Menu className="size-5" />
                </Button>
            </SheetTrigger>

            <SheetContent side="left" className="w-72 p-0">
                <SheetHeader className="border-b p-5 text-left">
                    <SheetTitle>NexForge Admin</SheetTitle>
                </SheetHeader>

                <AdminNavigation currentUrl={currentUrl} items={visibleNavigation} />
            </SheetContent>
        </Sheet>
    );
}
