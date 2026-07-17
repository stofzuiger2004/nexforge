import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import type { AdminOrderPaginator } from '@/types/admin';

type AdminPaginationProps = Pick<AdminOrderPaginator, 'meta' | 'links'>;

export function AdminPagination({ meta, links }: AdminPaginationProps) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
            <p className="text-sm text-muted-foreground">
                Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total}
            </p>

            <div className="flex items-center gap-2">
                {links.previous ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={links.previous}
                            preserveState
                            preserveScroll
                        >
                            <ChevronLeft className="size-4" />
                            Previous
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        <ChevronLeft className="size-4" />
                        Previous
                    </Button>
                )}

                <span className="px-2 text-sm text-muted-foreground">
                    Page {meta.current_page} of {meta.last_page}
                </span>

                {links.next ? (
                    <Button variant="outline" size="sm" asChild>
                        <Link href={links.next} preserveState preserveScroll>
                            Next
                            <ChevronRight className="size-4" />
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Next
                        <ChevronRight className="size-4" />
                    </Button>
                )}
            </div>
        </div>
    );
}
