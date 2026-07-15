import {
    Check,
    CircleAlert,
    Clock3,
    PackageCheck,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';

import { SystemVisual } from '@/components/storefront/system-visual';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import {
    formatMoney,
    formatSignedMoney,
} from '@/lib/money';
import type {
    ConfiguratorConfiguration,
    ConfiguratorGroup,
} from '@/types/configurator';

type ConfigurationSummaryProps = {
    configuration: ConfiguratorConfiguration;
    groups: ConfiguratorGroup[];

    displayedTotalInCents: number;

    saving: boolean;
    compact?: boolean;
};

export function ConfigurationSummary({
    configuration,
    groups,
    displayedTotalInCents,
    saving,
    compact = false,
}: ConfigurationSummaryProps) {
    const [reviewOpen, setReviewOpen] =
        useState(false);

    const currency =
        configuration.pricing.currency;

    const componentChange =
        displayedTotalInCents
        - configuration.pricing
            .base_price_in_cents;

    return (
        <div className="rounded-2xl border bg-background p-5 shadow-sm sm:p-6">
            {! compact && (
                <>
                    <SystemVisual
                        name={
                            configuration
                                .source_system
                                .name
                        }
                        image={
                            configuration
                                .source_system
                                .image
                        }
                        className="aspect-[16/9] rounded-xl"
                    />

                    <div className="mt-5">
                        <p className="text-xs text-muted-foreground">
                            Your configuration
                        </p>

                        <h2 className="mt-1 text-lg font-semibold">
                            {configuration.name}
                        </h2>
                    </div>

                    <Separator className="my-5" />
                </>
            )}

            <div className="space-y-3 text-sm">
                <PriceRow
                    label="Base system"
                    value={formatMoney(
                        configuration.pricing
                            .base_price_in_cents,
                        currency,
                    )}
                />

                <PriceRow
                    label="Component changes"
                    value={
                        componentChange === 0
                            ? 'Included'
                            : formatSignedMoney(
                                  componentChange,
                                  currency,
                              )
                    }
                />

                <Separator />

                <div className="flex items-end justify-between gap-4">
                    <div>
                        <p className="font-medium">
                            Total
                        </p>

                        <p className="mt-1 text-xs text-muted-foreground">
                            {configuration.pricing
                                .prices_include_tax
                                ? 'Including tax'
                                : 'Excluding tax'}
                        </p>
                    </div>

                    <p className="text-2xl font-semibold tracking-tight">
                        {formatMoney(
                            displayedTotalInCents,
                            currency,
                        )}
                    </p>
                </div>
            </div>

            <Separator className="my-5" />

            <SaveStatus saving={saving} />

            <div className="mt-5">
                <ValidationStatus
                    configuration={
                        configuration
                    }
                />
            </div>

            <div className="mt-5">
                <AvailabilityStatus
                    configuration={
                        configuration
                    }
                />
            </div>

            {! compact && (
                <>
                    <Separator className="my-5" />

                    <div>
                        <h3 className="text-sm font-semibold">
                            Selected components
                        </h3>

                        <div className="mt-4 space-y-3">
                            {groups.map(
                                (group) => (
                                    <a
                                        key={
                                            group.slot
                                        }
                                        href={`#group-${group.slot}`}
                                        className="flex items-start justify-between gap-4 text-sm"
                                    >
                                        <span className="text-muted-foreground">
                                            {
                                                group.label
                                            }
                                        </span>

                                        <span className="max-w-[13rem] truncate text-right font-medium">
                                            {group
                                                .selected
                                                ?.name
                                                ?? 'Not selected'}
                                        </span>
                                    </a>
                                ),
                            )}
                        </div>
                    </div>
                </>
            )}

            <ReviewDialog
                configuration={
                    configuration
                }
                groups={groups}
                displayedTotalInCents={
                    displayedTotalInCents
                }
                open={reviewOpen}
                onOpenChange={
                    setReviewOpen
                }
            />
        </div>
    );
}

function PriceRow({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">
                {label}
            </span>

            <span className="font-medium">
                {value}
            </span>
        </div>
    );
}

function SaveStatus({
    saving,
}: {
    saving: boolean;
}) {
    return (
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
            {saving ? (
                <>
                    <Clock3 className="size-3.5 animate-pulse" />
                    Saving component change…
                </>
            ) : (
                <>
                    <Check className="size-3.5" />
                    Changes are saved automatically
                </>
            )}
        </div>
    );
}

function ValidationStatus({
    configuration,
}: {
    configuration: ConfiguratorConfiguration;
}) {
    const errors =
        configuration.validation.errors;

    if (
        configuration.validation.status
        === 'valid'
        && configuration.validation
            .is_current
    ) {
        return (
            <div className="rounded-xl border bg-muted/25 p-4">
                <div className="flex gap-3">
                    <PackageCheck className="mt-0.5 size-5 shrink-0" />

                    <div>
                        <p className="text-sm font-medium">
                            Configuration compatible
                        </p>

                        <p className="mt-1 text-xs leading-5 text-muted-foreground">
                            The selected components
                            passed the current
                            compatibility checks.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-destructive/30 bg-destructive/5 p-4">
            <div className="flex gap-3">
                <CircleAlert className="mt-0.5 size-5 shrink-0 text-destructive" />

                <div>
                    <p className="text-sm font-medium">
                        {configuration.validation.label}
                    </p>

                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                        {errors.length > 0
                            ? `${errors.length} issue${errors.length === 1 ? '' : 's'} must be resolved before continuing.`
                            : 'The latest configuration has not been validated.'}
                    </p>
                </div>
            </div>
        </div>
    );
}

function AvailabilityStatus({
    configuration,
}: {
    configuration: ConfiguratorConfiguration;
}) {
    const availability =
        configuration.availability;

    return (
        <div className="flex items-start gap-3 text-sm">
            {availability.status
                === 'unavailable' ? (
                <TriangleAlert className="mt-0.5 size-4 shrink-0 text-destructive" />
            ) : (
                <Check className="mt-0.5 size-4 shrink-0" />
            )}

            <div>
                <p className="font-medium">
                    {availability.label}
                </p>

                {availability.available_builds
                    !== null
                    && availability.available_builds
                        > 0 && (
                    <p className="mt-1 text-xs text-muted-foreground">
                        {
                            availability
                                .available_builds
                        }{' '}
                        complete build
                        {availability
                            .available_builds
                            === 1
                            ? ''
                            : 's'}{' '}
                        currently available.
                    </p>
                )}
            </div>
        </div>
    );
}

type ReviewDialogProps = {
    configuration: ConfiguratorConfiguration;
    groups: ConfiguratorGroup[];
    displayedTotalInCents: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

function ReviewDialog({
    configuration,
    groups,
    displayedTotalInCents,
    open,
    onOpenChange,
}: ReviewDialogProps) {
    return (
        <Dialog
            open={open}
            onOpenChange={onOpenChange}
        >
            <DialogTrigger asChild>
                <Button
                    type="button"
                    className="mt-6 w-full"
                    disabled={
                        ! configuration.can_review
                    }
                >
                    Review configuration
                </Button>
            </DialogTrigger>

            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        Review your configuration
                    </DialogTitle>

                    <DialogDescription>
                        Check the selected components
                        and current total before moving
                        to the future checkout step.
                    </DialogDescription>
                </DialogHeader>

                <div className="mt-4 divide-y rounded-xl border">
                    {groups.map((group) => (
                        <div
                            key={group.slot}
                            className="flex items-start justify-between gap-5 px-4 py-3"
                        >
                            <span className="text-sm text-muted-foreground">
                                {group.label}
                            </span>

                            <span className="text-right text-sm font-medium">
                                {group.selected?.name
                                    ?? 'Not selected'}
                            </span>
                        </div>
                    ))}
                </div>

                <div className="flex items-center justify-between border-t pt-5">
                    <span className="font-medium">
                        Current total
                    </span>

                    <span className="text-2xl font-semibold">
                        {formatMoney(
                            displayedTotalInCents,
                            configuration.pricing
                                .currency,
                        )}
                    </span>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            onOpenChange(false)
                        }
                    >
                        Continue configuring
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}