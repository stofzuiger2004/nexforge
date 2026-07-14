import type { PropsWithChildren } from 'react';

import { SiteFooter } from '@/components/storefront/site-footer';
import { SiteHeader } from '@/components/storefront/site-header';

export default function StorefrontLayout({
    children,
}: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <SiteHeader />

            <main>{children}</main>

            <SiteFooter />
        </div>
    );
}