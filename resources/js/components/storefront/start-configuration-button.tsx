import { router } from '@inertiajs/react';
import { ArrowRight, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';

type StartConfigurationButtonProps = {
    systemSlug: string;

    variant?: 'default' | 'secondary' | 'outline' | 'ghost';

    label?: string;
    className?: string;
    disabled?: boolean;
};

export function StartConfigurationButton({
    systemSlug,
    variant = 'default',
    label = 'Configure this system',
    className,
    disabled = false,
}: StartConfigurationButtonProps) {
    const [processing, setProcessing] = useState(false);

    function startConfiguration(): void {
        if (processing || disabled) {
            return;
        }

        router.post(
            `/configure/start/${encodeURIComponent(systemSlug)}`,
            {},
            {
                preserveScroll: true,

                onStart: () => {
                    setProcessing(true);
                },

                onFinish: () => {
                    setProcessing(false);
                },
            },
        );
    }

    return (
        <Button
            type="button"
            variant={variant}
            className={className}
            disabled={processing || disabled}
            onClick={startConfiguration}
        >
            {processing ? (
                <>
                    <LoaderCircle className="size-4 animate-spin" />
                    Preparing…
                </>
            ) : (
                <>
                    {label}
                    <ArrowRight className="size-4" />
                </>
            )}
        </Button>
    );
}
