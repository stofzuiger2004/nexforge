import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type StatusBadgeProps = {
    status: string;
    className?: string;
};

const positiveStatuses = new Set([
    'paid',
    'confirmed',
    'completed',
    'fulfilled',
    'consumed',
    'committed',
    'refunded',
]);

const attentionStatuses = new Set([
    'failed',
    'cancelled',
    'expired',
    'charged_back',
    'partially_charged_back',
    'manual_review',
]);

export function StatusBadge({
    status,
    className,
}: StatusBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn(
                'rounded-full font-normal',

                positiveStatuses.has(status) &&
                    'border-emerald-600/25 bg-emerald-600/5 text-emerald-700 dark:text-emerald-400',

                attentionStatuses.has(status) &&
                    'border-destructive/30 bg-destructive/5 text-destructive',

                className,
            )}
        >
            {humanizeStatus(status)}
        </Badge>
    );
}

export function humanizeStatus(
    status: string,
): string {
    return status
        .split('_')
        .map(
            (part) =>
                part.charAt(0).toUpperCase() +
                part.slice(1),
        )
        .join(' ');
}