import {
    Check,
    LoaderCircle,
    Package,
    TriangleAlert,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import {
    formatMoney,
    formatSignedMoney,
} from '@/lib/money';
import { cn } from '@/lib/utils';
import type { ConfiguratorOption } from '@/types/configurator';

type ComponentOptionCardProps = {
    option: ConfiguratorOption;
    disabled: boolean;
    pending: boolean;

    onSelect: (
        option: ConfiguratorOption,
    ) => void;
};

export function ComponentOptionCard({
    option,
    disabled,
    pending,
    onSelect,
}: ComponentOptionCardProps) {
    const immediateDelta =
        option.price
            .delta_from_current_in_cents;

    const baseDelta =
        option.price
            .delta_from_base_in_cents;

    return (
        <button
            type="button"
            aria-pressed={option.is_selected}
            disabled={disabled}
            onClick={() => {
                if (
                    option.selectable
                    && ! pending
                ) {
                    onSelect(option);
                }
            }}
            className={cn(
                'relative w-full rounded-2xl border p-4 text-left transition',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',

                option.is_selected
                    ? 'border-foreground bg-muted/40'
                    : 'bg-background hover:border-muted-foreground/50 hover:bg-muted/15',

                pending &&
                    'border-foreground bg-muted/40',

                disabled &&
                    ! option.is_selected &&
                    'cursor-not-allowed opacity-60',
            )}
        >
            <div className="flex gap-4">
                <OptionImage option={option} />

                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            {option.brand && (
                                <p className="text-xs text-muted-foreground">
                                    {option.brand}
                                </p>
                            )}

                            <h4 className="mt-1 text-sm font-semibold leading-5">
                                {option.name}
                            </h4>

                            <p className="mt-1 text-xs text-muted-foreground">
                                SKU {option.sku}
                            </p>
                        </div>

                        <div className="shrink-0 text-right">
                            {pending ? (
                                <div className="flex items-center gap-2 text-sm font-medium">
                                    <LoaderCircle className="size-4 animate-spin" />
                                    Saving
                                </div>
                            ) : option.is_selected ? (
                                <Badge className="rounded-full">
                                    <Check className="size-3" />
                                    Selected
                                </Badge>
                            ) : (
                                <p
                                    className={cn(
                                        'text-sm font-semibold',

                                        immediateDelta
                                            > 0
                                            ? 'text-foreground'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {immediateDelta
                                        === 0
                                        ? 'No change'
                                        : formatSignedMoney(
                                              immediateDelta,
                                              option.price
                                                  .currency,
                                          )}
                                </p>
                            )}

                            {! option.is_selected && (
                                <p className="mt-1 text-[11px] text-muted-foreground">
                                    New total impact
                                </p>
                            )}
                        </div>
                    </div>

                    {option.description && (
                        <p className="mt-3 line-clamp-2 text-xs leading-5 text-muted-foreground">
                            {option.description}
                        </p>
                    )}

                    {option.specifications.length
                        > 0 && (
                        <dl className="mt-3 flex flex-wrap gap-2">
                            {option.specifications.map(
                                (
                                    specification,
                                ) => (
                                    <div
                                        key={
                                            specification.key
                                        }
                                        className="rounded-lg border bg-background px-2.5 py-1.5"
                                    >
                                        <dt className="text-[10px] text-muted-foreground">
                                            {
                                                specification.label
                                            }
                                        </dt>

                                        <dd className="mt-0.5 text-[11px] font-medium">
                                            {
                                                specification.value
                                            }
                                        </dd>
                                    </div>
                                ),
                            )}
                        </dl>
                    )}

                    <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t pt-3 text-xs">
                        <AvailabilityLabel
                            option={option}
                        />

                        {option.compatibility
                            .status
                            === 'compatible' && (
                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                <Check className="size-3.5" />
                                Compatible
                            </span>
                        )}

                        {option.compatibility
                            .status
                            !== 'compatible' && (
                            <span className="flex items-center gap-1.5 text-destructive">
                                <TriangleAlert className="size-3.5" />

                                {option.compatibility
                                    .status
                                    === 'requires_changes'
                                    ? 'Requires another change'
                                    : 'Compatibility note'}
                            </span>
                        )}

                        {option.is_selected && (
                            <span className="ml-auto text-muted-foreground">
                                {baseDelta === 0
                                    ? 'Included in base price'
                                    : `${formatSignedMoney(
                                          baseDelta,
                                          option.price
                                              .currency,
                                      )} from base`}
                            </span>
                        )}
                    </div>

                    {option.compatibility
                        .messages[0] && (
                        <p className="mt-3 rounded-lg bg-muted/50 px-3 py-2 text-xs leading-5 text-muted-foreground">
                            {
                                option.compatibility
                                    .messages[0]
                                    .message
                            }
                        </p>
                    )}
                </div>
            </div>

            <span className="sr-only">
                Current price{' '}
                {formatMoney(
                    option.price
                        .amount_in_cents,
                    option.price.currency,
                )}
            </span>
        </button>
    );
}

function OptionImage({
    option,
}: {
    option: ConfiguratorOption;
}) {
    return (
        <div className="relative size-20 shrink-0 overflow-hidden rounded-xl border bg-muted/30">
            {option.image ? (
                <img
                    src={option.image.url}
                    alt={option.image.alt}
                    loading="lazy"
                    className="absolute inset-0 size-full object-contain p-2"
                />
            ) : (
                <div className="absolute inset-0 grid place-items-center">
                    <Package className="size-7 text-muted-foreground" />
                </div>
            )}
        </div>
    );
}

function AvailabilityLabel({
    option,
}: {
    option: ConfiguratorOption;
}) {
    const builds =
        option.availability.available_builds;

    let label = option.availability.label;

    if (builds === 1) {
        label =
            'One complete build available';
    } else if (
        builds !== null
        && builds > 1
    ) {
        label = `${builds} complete builds available`;
    }

    return (
        <span className="text-muted-foreground">
            {label}
        </span>
    );
}