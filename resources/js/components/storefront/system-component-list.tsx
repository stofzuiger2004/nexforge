import {
    Box,
    Cpu,
    Disc3,
    Fan,
    HardDrive,
    LayoutGrid,
    MemoryStick,
    Monitor,
    Package,
    Zap,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import type { SystemDetailComponent } from '@/types/storefront';

type SystemComponentListProps = {
    components: SystemDetailComponent[];
};

const slotIcons: Record<string, LucideIcon> = {
    cpu: Cpu,
    motherboard: LayoutGrid,
    graphics_card: Monitor,
    memory: MemoryStick,

    primary_storage: HardDrive,
    secondary_storage: HardDrive,

    power_supply: Zap,
    case: Box,

    cpu_cooler: Fan,
    case_fan: Fan,

    operating_system: Disc3,
};

export function SystemComponentList({
    components,
}: SystemComponentListProps) {
    if (components.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed p-10 text-center">
                <h3 className="font-semibold">
                    No components have been added
                </h3>

                <p className="mt-2 text-sm text-muted-foreground">
                    Add system components in the
                    catalogue before publishing this
                    system.
                </p>
            </div>
        );
    }

    return (
        <div className="overflow-hidden rounded-2xl border">
            {components.map(
                (component, index) => (
                    <ComponentRow
                        key={component.id}
                        component={component}
                        hasBorder={index > 0}
                    />
                ),
            )}
        </div>
    );
}

type ComponentRowProps = {
    component: SystemDetailComponent;
    hasBorder: boolean;
};

function ComponentRow({
    component,
    hasBorder,
}: ComponentRowProps) {
    const Icon =
        slotIcons[component.slot] ?? Package;

    return (
        <article
            className={
                hasBorder
                    ? 'border-t'
                    : undefined
            }
        >
            <div className="grid gap-4 p-5 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:p-6">
                <span className="grid size-10 place-items-center rounded-xl bg-muted">
                    <Icon className="size-5 text-muted-foreground" />
                </span>

                <div className="min-w-0">
                    <p className="text-xs font-medium uppercase tracking-[0.12em] text-muted-foreground">
                        {component.slot_label}
                    </p>

                    <h3 className="mt-2 font-semibold">
                        {component.name}
                    </h3>

                    <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {component.brand && (
                            <span>
                                {component.brand}
                            </span>
                        )}

                        <span>
                            SKU {component.sku}
                        </span>

                        {component.quantity > 1 && (
                            <span>
                                Quantity{' '}
                                {component.quantity}
                            </span>
                        )}
                    </div>

                    {component.specifications.length
                        > 0 && (
                        <dl className="mt-4 flex flex-wrap gap-2">
                            {component.specifications.map(
                                (
                                    specification,
                                ) => (
                                    <div
                                        key={
                                            specification.key
                                        }
                                        className="rounded-lg border bg-muted/20 px-3 py-2"
                                    >
                                        <dt className="text-[11px] text-muted-foreground">
                                            {
                                                specification.label
                                            }
                                        </dt>

                                        <dd className="mt-0.5 text-xs font-medium">
                                            {
                                                specification.value
                                            }
                                        </dd>
                                    </div>
                                ),
                            )}
                        </dl>
                    )}
                </div>

                <div className="sm:text-right">
                    <span className="inline-flex rounded-full border px-2.5 py-1 text-xs text-muted-foreground">
                        {component.is_replaceable
                            ? 'Can be changed'
                            : 'Fixed component'}
                    </span>
                </div>
            </div>
        </article>
    );
}