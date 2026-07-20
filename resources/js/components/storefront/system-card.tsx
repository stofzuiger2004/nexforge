import { Link } from '@inertiajs/react';
import { Cpu, HardDrive, MemoryStick, Monitor, Dot } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { StartConfigurationButton } from '@/components/storefront/start-configuration-button';
import { SystemVisual } from '@/components/storefront/system-visual';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/money';
import type {
    FeaturedSystem,
    SystemComponentSummary,
} from '@/types/storefront';

type SystemCardProps = {
    system: FeaturedSystem;
};

export function SystemCard({ system }: SystemCardProps) {
    const detailsUrl = `/gaming-pcs/${encodeURIComponent(system.slug)}`;

    const price = system.price
        ? formatMoney(system.price.amount_in_cents, system.price.currency)
        : 'Price on request';

    const compareAtPrice = system.price?.compare_at_amount_in_cents
        ? formatMoney(
              system.price.compare_at_amount_in_cents,
              system.price.currency,
          )
        : null;

    return (
        <Card className="group gap-0 overflow-hidden rounded-2xl p-0 shadow-none transition duration-300 hover:-translate-y-1 hover:shadow-lg">
            <SystemVisual
                name={system.name}
                image={system.image}
                className="rounded-none border-0 border-b"
            />

            <CardHeader className="space-y-4 p-6">

                <div>
                    <CardTitle className="text-xl">
                        <div className="w-full inline-flex justify-between">
                            <div>
                                <p className="uppercase text-xs text-gray-400 font-light">1080p gaming</p>
                                <Link
                                    href={detailsUrl}
                                    className="transition-colors hover:text-muted-foreground"
                                >
                                    {system.name}
                                </Link>
                            </div>
                            <div>
                                <p className="uppercase text-xs text-gray-400 font-light text-end">From</p>
                                <p className="font-semibold tracking-tight">
                                    {price}
                                </p>
                            </div>
                            
                        </div>
                        {compareAtPrice && (
                            <p className="text-sm text-muted-foreground line-through">
                                {compareAtPrice}
                            </p>
                        )}
                    </CardTitle>
                </div>
            </CardHeader>

            <CardContent className="space-y-3 px-6 pb-6">
                <SpecificationRow
                    icon={Cpu}
                    label="Processor"
                    component={system.components.processor}
                />

                <SpecificationRow
                    icon={Monitor}
                    label="Graphics"
                    component={system.components.graphics_card}
                />

                <SpecificationRow
                    icon={MemoryStick}
                    label="Memory"
                    component={system.components.memory}
                />

                <SpecificationRow
                    icon={HardDrive}
                    label="Storage"
                    component={system.components.storage}
                />
            </CardContent>

            <CardFooter className="border-t bg-muted/20 p-6 flex justify-center">
                    <StartConfigurationButton
                        systemSlug={system.slug}
                        label="Configure & Buy"
                        className="w-full sm:w-auto"
                        disabled={!system.is_configurable}
                    />
            </CardFooter>
        </Card>
    );
}

type SpecificationRowProps = {
    icon: LucideIcon;
    label: string;
    component: SystemComponentSummary | null;
};

function SpecificationRow({
    icon: Icon,
    label,
    component,
}: SpecificationRowProps) {
    return (
        <div className="flex items-start gap-3">
            <span className="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-muted">
                <Icon className="size-4 text-muted-foreground" />
            </span>

            <div className="min-w-0">
                <p className="text-xs text-muted-foreground">{label}</p>

                <p className="truncate text-sm font-medium">
                    {component?.name ?? 'To be confirmed'}
                </p>
            </div>
        </div>
    );
}
