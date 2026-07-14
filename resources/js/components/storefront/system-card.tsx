import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Cpu,
    HardDrive,
    MemoryStick,
    Monitor,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { SystemVisual } from '@/components/storefront/system-visual';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

export function SystemCard({
    system,
}: SystemCardProps) {
    const configureUrl =
        `/configure?system=${encodeURIComponent(system.slug)}`;

    const price = system.price
        ? formatMoney(
              system.price.amount_in_cents,
              system.price.currency,
          )
        : 'Price on request';

    const compareAtPrice =
        system.price?.compare_at_amount_in_cents
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
                <div className="flex items-center justify-between gap-4">
                    <Badge
                        variant="outline"
                        className="rounded-full font-normal"
                    >
                        {system.availability.label}
                    </Badge>

                    <span className="text-xs text-muted-foreground">
                        Customizable
                    </span>
                </div>

                <div>
                    <CardTitle className="text-xl">
                        <Link
                            href={configureUrl}
                            className="transition-colors hover:text-muted-foreground"
                        >
                            {system.name}
                        </Link>
                    </CardTitle>

                    <p className="mt-2 min-h-12 text-sm leading-6 text-muted-foreground">
                        {system.description ??
                            'A balanced gaming system ready to configure.'}
                    </p>
                </div>
            </CardHeader>

            <CardContent className="space-y-3 px-6 pb-6">
                <SpecificationRow
                    icon={Cpu}
                    label="Processor"
                    component={
                        system.components.processor
                    }
                />

                <SpecificationRow
                    icon={Monitor}
                    label="Graphics"
                    component={
                        system.components.graphics_card
                    }
                />

                <SpecificationRow
                    icon={MemoryStick}
                    label="Memory"
                    component={
                        system.components.memory
                    }
                />

                <SpecificationRow
                    icon={HardDrive}
                    label="Storage"
                    component={
                        system.components.storage
                    }
                />
            </CardContent>

            <CardFooter className="flex items-end justify-between gap-4 border-t bg-muted/20 p-6">
                <div>
                    <p className="text-xs text-muted-foreground">
                        Starting at
                    </p>

                    <div className="mt-1 flex flex-wrap items-baseline gap-2">
                        <p className="text-2xl font-semibold tracking-tight">
                            {price}
                        </p>

                        {compareAtPrice && (
                            <p className="text-sm text-muted-foreground line-through">
                                {compareAtPrice}
                            </p>
                        )}
                    </div>
                </div>

                <Button asChild>
                    <Link href={configureUrl}>
                        Configure
                        <ArrowRight className="size-4" />
                    </Link>
                </Button>
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
                <p className="text-xs text-muted-foreground">
                    {label}
                </p>

                <p className="truncate text-sm font-medium">
                    {component?.name ??
                        'To be confirmed'}
                </p>
            </div>
        </div>
    );
}