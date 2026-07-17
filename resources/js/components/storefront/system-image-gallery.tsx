import { useState } from 'react';

import { SystemVisual } from '@/components/storefront/system-visual';
import { cn } from '@/lib/utils';
import type { SystemDetailImage } from '@/types/storefront';

type SystemImageGalleryProps = {
    name: string;
    images: SystemDetailImage[];
};

export function SystemImageGallery({ name, images }: SystemImageGalleryProps) {
    const [selectedIndex, setSelectedIndex] = useState(0);

    const selectedImage = images[selectedIndex] ?? null;

    return (
        <div>
            <SystemVisual
                name={name}
                image={selectedImage}
                eager
                className="aspect-square rounded-3xl"
            />

            {images.length > 1 && (
                <div className="mt-4 grid grid-cols-4 gap-3">
                    {images.map((image, index) => (
                        <button
                            key={image.id}
                            type="button"
                            onClick={() => setSelectedIndex(index)}
                            aria-label={`View image ${index + 1} of ${name}`}
                            aria-pressed={selectedIndex === index}
                            className={cn(
                                'relative aspect-square overflow-hidden rounded-xl border bg-muted transition',
                                'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
                                selectedIndex === index
                                    ? 'border-foreground'
                                    : 'hover:border-muted-foreground/50',
                            )}
                        >
                            <img
                                src={image.url}
                                alt=""
                                loading="lazy"
                                className="absolute inset-0 size-full object-cover"
                            />
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
