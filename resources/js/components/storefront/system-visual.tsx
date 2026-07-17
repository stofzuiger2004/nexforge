import { Cpu } from 'lucide-react';

import { cn } from '@/lib/utils';
import type { SystemImage } from '@/types/storefront';

type SystemVisualProps = {
    name: string;
    image: SystemImage | null;
    className?: string;
    eager?: boolean;
};

export function SystemVisual({
    name,
    image,
    className,
    eager = false,
}: SystemVisualProps) {
    return (
        <div
            className={cn(
                'relative isolate aspect-[4/3] overflow-hidden rounded-2xl border bg-muted/40',
                className,
            )}
        >
            {image ? (
                <img
                    src={image.url}
                    alt={image.alt}
                    loading={eager ? 'eager' : 'lazy'}
                    className="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-[1.02]"
                />
            ) : (
                <FallbackSystemVisual name={name} />
            )}
        </div>
    );
}

function FallbackSystemVisual({ name }: { name: string }) {
    return (
        <div
            role="img"
            aria-label={`${name} gaming PC`}
            className="absolute inset-0 flex items-center justify-center"
        >
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_50%_40%,rgba(0,0,0,0.07),transparent_55%)] dark:bg-[radial-gradient(circle_at_50%_40%,rgba(255,255,255,0.08),transparent_55%)]" />

            <div className="relative aspect-[0.72] h-[74%] rounded-[1.6rem] border border-white/10 bg-zinc-950 p-3 shadow-2xl">
                <div className="relative flex size-full items-center justify-center overflow-hidden rounded-[1.15rem] border border-white/10 bg-zinc-900">
                    <div className="absolute top-5 left-5 size-20 rounded-full border border-white/15">
                        <div className="absolute inset-2 rounded-full border border-white/10" />
                        <div className="absolute inset-[1.15rem] rounded-full bg-white/5" />
                    </div>

                    <div className="absolute top-5 right-4 h-24 w-1 rounded-full bg-white/10" />

                    <Cpu className="size-16 text-white/60" />

                    <div className="absolute bottom-6 h-1.5 w-24 rounded-full bg-white/15" />
                </div>
            </div>
        </div>
    );
}
