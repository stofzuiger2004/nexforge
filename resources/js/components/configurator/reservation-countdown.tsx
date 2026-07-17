import { Clock3, TriangleAlert } from 'lucide-react';
import { useEffect, useState } from 'react';

type ReservationCountdownProps = {
    expiresAt: string;

    onExpired?: () => void;
};

export function ReservationCountdown({
    expiresAt,
    onExpired,
}: ReservationCountdownProps) {
    const [remainingSeconds, setRemainingSeconds] = useState(
        calculateRemainingSeconds(expiresAt),
    );

    useEffect(() => {
        const interval = window.setInterval(() => {
            const remaining = calculateRemainingSeconds(expiresAt);

            setRemainingSeconds(remaining);

            if (remaining === 0) {
                onExpired?.();
            }
        }, 1000);

        return () => window.clearInterval(interval);
    }, [expiresAt, onExpired]);

    if (remainingSeconds === 0) {
        return (
            <div className="flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/5 p-4">
                <TriangleAlert className="mt-0.5 size-5 shrink-0 text-destructive" />

                <div>
                    <p className="text-sm font-medium">Stock hold expired</p>

                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                        Refresh the reservation before creating the order.
                    </p>
                </div>
            </div>
        );
    }

    const minutes = Math.floor(remainingSeconds / 60);

    const seconds = remainingSeconds % 60;

    return (
        <div className="flex items-start gap-3 rounded-xl border bg-muted/20 p-4">
            <Clock3 className="mt-0.5 size-5 shrink-0" />

            <div>
                <p className="text-sm font-medium">
                    Stock reserved for {minutes}:
                    {seconds.toString().padStart(2, '0')}
                </p>

                <p className="mt-1 text-xs leading-5 text-muted-foreground">
                    The selected components are temporarily held while you
                    complete the order details.
                </p>
            </div>
        </div>
    );
}

function calculateRemainingSeconds(expiresAt: string): number {
    const expiration = new Date(expiresAt).getTime();

    return Math.max(0, Math.ceil((expiration - Date.now()) / 1000));
}
