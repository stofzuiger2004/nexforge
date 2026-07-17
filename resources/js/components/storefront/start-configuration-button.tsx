import { router } from '@inertiajs/react';
import { ArrowRight, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';

type StartConfigurationButtonProps = {
    systemSlug: string;
    variant?: 'default' | 'secondary';
    className?: string;
};

export function StartConfigurationButton({
    systemSlug,
    variant = 'default',
    className,
}: StartConfigurationButtonProps) {
    const [processing, setProcessing] = useState(false);

    function startConfiguration() {
        router.post(
            `/configure/start/${encodeURIComponent(systemSlug)}`,
            {},
            {
                preserveScroll: true,

                onStart: () => setProcessing(true),

                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <Button
            type="button"
            size="lg"
            variant={variant}
            className={className}
            disabled={processing}
            onClick={startConfiguration}
        >
            {processing ? (
                <>
                    <LoaderCircle className="size-4 animate-spin" />
                    Preparing configuration
                </>
            ) : (
                <>
                    Configure this system
                    <ArrowRight className="size-4" />
                </>
            )}
        </Button>
    );
}
