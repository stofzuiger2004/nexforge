import { ChevronUp } from 'lucide-react';

import { ConfigurationSummary } from '@/components/configurator/configuration-summary';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { formatMoney } from '@/lib/money';
import type {
    ConfiguratorConfiguration,
    ConfiguratorGroup,
} from '@/types/configurator';

type MobileConfigurationSummaryProps = {
    configuration: ConfiguratorConfiguration;
    groups: ConfiguratorGroup[];
    displayedTotalInCents: number;
    saving: boolean;
};

export function MobileConfigurationSummary({
    configuration,
    groups,
    displayedTotalInCents,
    saving,
}: MobileConfigurationSummaryProps) {
    return (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t bg-background/95 p-3 backdrop-blur-xl lg:hidden">
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-4">
                <div>
                    <p className="text-xs text-muted-foreground">
                        Current total
                    </p>

                    <p className="text-lg font-semibold">
                        {formatMoney(
                            displayedTotalInCents,
                            configuration.pricing.currency,
                        )}
                    </p>
                </div>

                <Sheet>
                    <SheetTrigger asChild>
                        <Button type="button">
                            Summary
                            <ChevronUp className="size-4" />
                        </Button>
                    </SheetTrigger>

                    <SheetContent
                        side="bottom"
                        className="max-h-[88vh] overflow-y-auto rounded-t-3xl"
                    >
                        <SheetHeader className="text-left">
                            <SheetTitle>Configuration summary</SheetTitle>
                        </SheetHeader>

                        <div className="mt-5 pb-6">
                            <ConfigurationSummary
                                configuration={configuration}
                                groups={groups}
                                displayedTotalInCents={displayedTotalInCents}
                                saving={saving}
                                compact
                            />
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    );
}
