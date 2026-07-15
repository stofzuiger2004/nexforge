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
    TriangleAlert,
    Zap,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { ComponentOptionCard } from '@/components/configurator/component-option-card';
import {
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { formatSignedMoney } from '@/lib/money';
import type {
    ConfiguratorGroup,
    ConfiguratorOption,
} from '@/types/configurator';

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

type ConfiguratorGroupProps = {
    group: ConfiguratorGroup;

    requestPending: boolean;
    pendingVariantId: number | null;

    onSelect: (
        group: ConfiguratorGroup,
        option: ConfiguratorOption,
    ) => void;
};

export function ConfiguratorGroupSection({
    group,
    requestPending,
    pendingVariantId,
    onSelect,
}: ConfiguratorGroupProps) {
    const Icon =
        slotIcons[group.slot]
        ?? Package;

    const currency =
        group.selected
            ?.price.currency
        ?? 'EUR';

    return (
        <AccordionItem
            value={group.slot}
            id={`group-${group.slot}`}
            className="scroll-mt-24 overflow-hidden rounded-2xl border px-0"
        >
            <AccordionTrigger className="px-5 py-5 hover:no-underline sm:px-6">
                <div className="flex min-w-0 flex-1 items-center gap-4 pr-4 text-left">
                    <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-muted">
                        <Icon className="size-5" />
                    </span>

                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="font-semibold">
                                {group.label}
                            </h2>

                            {! group.is_replaceable && (
                                <Badge
                                    variant="outline"
                                    className="rounded-full font-normal"
                                >
                                    Fixed
                                </Badge>
                            )}

                            {group.issues.length
                                > 0 && (
                                <Badge
                                    variant="destructive"
                                    className="rounded-full"
                                >
                                    <TriangleAlert className="size-3" />
                                    {
                                        group.issues
                                            .length
                                    }
                                </Badge>
                            )}
                        </div>

                        <p className="mt-1 truncate text-sm font-normal text-muted-foreground">
                            {group.selected?.name
                                ?? 'No component selected'}
                        </p>
                    </div>

                    <div className="hidden shrink-0 text-right sm:block">
                        <p className="text-xs font-normal text-muted-foreground">
                            Compared with base
                        </p>

                        <p className="mt-1 text-sm font-medium">
                            {group
                                .selected_price_change_from_base_in_cents
                                === 0
                                ? 'Included'
                                : formatSignedMoney(
                                      group
                                          .selected_price_change_from_base_in_cents,
                                      currency,
                                  )}
                        </p>
                    </div>
                </div>
            </AccordionTrigger>

            <AccordionContent className="border-t px-5 pb-6 pt-5 sm:px-6">
                <p className="max-w-3xl text-sm leading-6 text-muted-foreground">
                    {group.description}
                </p>

                {group.issues.length > 0 && (
                    <div className="mt-4 space-y-2">
                        {group.issues.map(
                            (issue) => (
                                <div
                                    key={
                                        issue.rule_key
                                    }
                                    className="flex gap-3 rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm"
                                >
                                    <TriangleAlert className="mt-0.5 size-4 shrink-0 text-destructive" />

                                    <p className="leading-6">
                                        {
                                            issue.message
                                        }
                                    </p>
                                </div>
                            ),
                        )}
                    </div>
                )}

                {group.can_edit && (
                    <p className="mt-5 text-xs text-muted-foreground">
                        Price differences show the
                        immediate change against your
                        current selection.
                    </p>
                )}

                <div className="mt-5 grid gap-3 xl:grid-cols-2">
                    {group.options.map(
                        (option) => (
                            <ComponentOptionCard
                                key={option.id}
                                option={option}
                                disabled={
                                    requestPending
                                    || ! group.can_edit
                                }
                                pending={
                                    pendingVariantId
                                    === option.id
                                }
                                onSelect={(
                                    selectedOption,
                                ) =>
                                    onSelect(
                                        group,
                                        selectedOption,
                                    )
                                }
                            />
                        ),
                    )}
                </div>

                {! group.can_edit && (
                    <p className="mt-4 text-xs text-muted-foreground">
                        This component is part of the
                        fixed starting configuration.
                    </p>
                )}
            </AccordionContent>
        </AccordionItem>
    );
}