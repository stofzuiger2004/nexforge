import {
    Head,
    Link,
    router,
} from '@inertiajs/react';
import {
    Check,
    ChevronRight,
    CircleAlert,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import { ConfigurationSummary } from '@/components/configurator/configuration-summary';
import { ConfiguratorGroupSection } from '@/components/configurator/configurator-group';
import { MobileConfigurationSummary } from '@/components/configurator/mobile-configuration-summary';
import {
    Accordion,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import StorefrontLayout from '@/layouts/storefront-layout';
import type {
    ConfiguratorGroup,
    ConfiguratorOption,
    ConfiguratorPageProps,
} from '@/types/configurator';

type PendingSelection = {
    slot: string;
    variantId: number;
    priceDeltaInCents: number;
};

export default function ConfiguratorShow({
    configuration,
    groups,
}: ConfiguratorPageProps) {
    const [
        pendingSelection,
        setPendingSelection,
    ] = useState<PendingSelection | null>(
        null,
    );

    const [
        requestError,
        setRequestError,
    ] = useState<string | null>(
        null,
    );

    const defaultOpenGroups = useMemo(
        () => {
            const issueGroups = groups
                .filter(
                    (group) =>
                        group.issues.length > 0,
                )
                .map(
                    (group) => group.slot,
                );

            if (issueGroups.length > 0) {
                return issueGroups;
            }

            return groups[0]
                ? [groups[0].slot]
                : [];
        },
        [groups],
    );

    const displayedTotalInCents =
        configuration.pricing
            .total_in_cents
        + (
            pendingSelection
                ?.priceDeltaInCents
            ?? 0
        );

    const requestPending =
        pendingSelection !== null;

    function selectOption(
        group: ConfiguratorGroup,
        option: ConfiguratorOption,
    ) {
        if (
            requestPending
            || ! group.can_edit
            || option.is_selected
            || ! option.selectable
        ) {
            return;
        }

        setRequestError(null);

        setPendingSelection({
            slot: group.slot,
            variantId: option.id,

            priceDeltaInCents:
                option.price
                    .delta_from_current_in_cents,
        });

        router.patch(
            `/configure/${configuration.public_id}/components/${group.slot}`,
            {
                variant_id: option.id,
                quantity: 1,
            },
            {
                preserveScroll: true,
                preserveState: true,

                onError: (errors) => {
                    const variantError =
                        errors.variant_id;

                    setRequestError(
                        typeof variantError
                            === 'string'
                            ? variantError
                            : 'The component could not be selected.',
                    );
                },

                onFinish: () => {
                    setPendingSelection(null);
                },
            },
        );
    }

    return (
        <StorefrontLayout>
            <Head
                title={`Configure ${configuration.source_system.name}`}
            />

            <ConfiguratorHeader
                configurationName={
                    configuration.name
                }
                systemName={
                    configuration
                        .source_system
                        .name
                }
                systemUrl={
                    configuration
                        .source_system
                        .details_url
                }
                status={
                    configuration
                        .validation
                        .status
                }
            />

            <section className="pb-32 pt-10 lg:pb-20">
                <div className="mx-auto grid max-w-7xl items-start gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_23rem] lg:px-8">
                    <div>
                        <div className="mb-8">
                            <p className="text-sm font-medium text-muted-foreground">
                                Components
                            </p>

                            <h2 className="mt-2 text-3xl font-semibold tracking-tight">
                                Build your system
                            </h2>

                            <p className="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground">
                                Select an option in each
                                section. Price differences
                                show exactly how that
                                choice changes your
                                current total.
                            </p>
                        </div>

                        {requestError && (
                            <div className="mb-5 flex gap-3 rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm">
                                <CircleAlert className="mt-0.5 size-4 shrink-0 text-destructive" />

                                <p>
                                    {requestError}
                                </p>
                            </div>
                        )}

                        <Accordion
                            type="multiple"
                            defaultValue={
                                defaultOpenGroups
                            }
                            className="space-y-4"
                        >
                            {groups.map((group) => (
                                <ConfiguratorGroupSection
                                    key={group.slot}
                                    group={group}
                                    requestPending={
                                        requestPending
                                    }
                                    pendingVariantId={
                                        pendingSelection
                                            ?.slot
                                        === group.slot
                                            ? pendingSelection
                                                  .variantId
                                            : null
                                    }
                                    onSelect={
                                        selectOption
                                    }
                                />
                            ))}
                        </Accordion>
                    </div>

                    <aside className="hidden lg:block">
                        <div className="sticky top-24">
                            <ConfigurationSummary
                                configuration={
                                    configuration
                                }
                                groups={groups}
                                displayedTotalInCents={
                                    displayedTotalInCents
                                }
                                saving={
                                    requestPending
                                }
                            />
                        </div>
                    </aside>
                </div>
            </section>

            <MobileConfigurationSummary
                configuration={
                    configuration
                }
                groups={groups}
                displayedTotalInCents={
                    displayedTotalInCents
                }
                saving={requestPending}
            />
        </StorefrontLayout>
    );
}

type ConfiguratorHeaderProps = {
    configurationName: string;
    systemName: string;
    systemUrl: string;
    status: string;
};

function ConfiguratorHeader({
    configurationName,
    systemName,
    systemUrl,
    status,
}: ConfiguratorHeaderProps) {
    const isValid = status === 'valid';

    return (
        <section className="border-b bg-muted/20">
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <nav
                    aria-label="Breadcrumb"
                    className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    <Link
                        href="/"
                        className="hover:text-foreground"
                    >
                        Home
                    </Link>

                    <ChevronRight className="size-4" />

                    <Link
                        href="/gaming-pcs"
                        className="hover:text-foreground"
                    >
                        Gaming PCs
                    </Link>

                    <ChevronRight className="size-4" />

                    <Link
                        href={systemUrl}
                        className="hover:text-foreground"
                    >
                        {systemName}
                    </Link>

                    <ChevronRight className="size-4" />

                    <span
                        className="text-foreground"
                        aria-current="page"
                    >
                        Configure
                    </span>
                </nav>

                <div className="mt-7 flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                    <div>
                        <Badge
                            variant="outline"
                            className="rounded-full"
                        >
                            {isValid ? (
                                <>
                                    <Check className="size-3" />
                                    Compatible
                                </>
                            ) : (
                                <>
                                    <CircleAlert className="size-3" />
                                    Needs attention
                                </>
                            )}
                        </Badge>

                        <h1 className="mt-4 text-3xl font-semibold tracking-[-0.03em] sm:text-4xl">
                            Configure{' '}
                            {configurationName}
                        </h1>

                        <p className="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground">
                            Customize the starting
                            system while pricing,
                            compatibility, and component
                            availability update after
                            every selection.
                        </p>
                    </div>

                    <ConfigurationSteps />
                </div>
            </div>
        </section>
    );
}

function ConfigurationSteps() {
    return (
        <ol className="flex items-center gap-2 text-xs">
            <Step
                number="1"
                label="Configure"
                active
            />

            <span className="h-px w-5 bg-border" />

            <Step
                number="2"
                label="Review"
            />

            <span className="h-px w-5 bg-border" />

            <Step
                number="3"
                label="Payment"
            />
        </ol>
    );
}

function Step({
    number,
    label,
    active = false,
}: {
    number: string;
    label: string;
    active?: boolean;
}) {
    return (
        <li className="flex items-center gap-2">
            <span
                className={
                    active
                        ? 'grid size-7 place-items-center rounded-full bg-foreground text-background'
                        : 'grid size-7 place-items-center rounded-full border bg-background text-muted-foreground'
                }
            >
                {number}
            </span>

            <span
                className={
                    active
                        ? 'font-medium'
                        : 'text-muted-foreground'
                }
            >
                {label}
            </span>
        </li>
    );
}