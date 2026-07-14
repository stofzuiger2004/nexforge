import { Link } from '@inertiajs/react';
import { Cpu } from 'lucide-react';

const shopLinks = [
    {
        label: 'Gaming PCs',
        href: '/gaming-pcs',
    },
    {
        label: 'Configurator',
        href: '/configure',
    },
    {
        label: 'Components',
        href: '/components',
    },
];

const helpLinks = [
    {
        label: 'Support',
        href: '/support',
    },
    {
        label: 'Contact',
        href: '/contact',
    },
    {
        label: 'Delivery',
        href: '/delivery',
    },
];

export function SiteFooter() {
    return (
        <footer className="border-t bg-muted/20">
            <div className="mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 md:grid-cols-[1fr_auto] lg:px-8">
                <div className="max-w-sm">
                    <Link
                        href="/"
                        className="inline-flex items-center gap-3"
                    >
                        <span className="grid size-9 place-items-center rounded-xl bg-foreground text-background">
                            <Cpu className="size-5" />
                        </span>

                        <span className="text-lg font-semibold tracking-tight">
                            NexForge
                        </span>
                    </Link>

                    <p className="mt-4 text-sm leading-6 text-muted-foreground">
                        Custom gaming systems assembled,
                        validated, and tested before they
                        reach your desk.
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-12 sm:gap-20">
                    <FooterGroup
                        title="Shop"
                        links={shopLinks}
                    />

                    <FooterGroup
                        title="Help"
                        links={helpLinks}
                    />
                </div>
            </div>

            <div className="border-t">
                <div className="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-6 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <p>
                        © {new Date().getFullYear()}{' '}
                        NexForge.
                    </p>

                    <div className="flex gap-5">
                        <Link
                            href="/privacy"
                            className="hover:text-foreground"
                        >
                            Privacy
                        </Link>

                        <Link
                            href="/terms"
                            className="hover:text-foreground"
                        >
                            Terms
                        </Link>
                    </div>
                </div>
            </div>
        </footer>
    );
}

type FooterGroupProps = {
    title: string;

    links: {
        label: string;
        href: string;
    }[];
};

function FooterGroup({
    title,
    links,
}: FooterGroupProps) {
    return (
        <nav>
            <h2 className="text-sm font-semibold">
                {title}
            </h2>

            <ul className="mt-4 space-y-3">
                {links.map((link) => (
                    <li key={link.href}>
                        <Link
                            href={link.href}
                            className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {link.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}