import {
    Head,
    Link,
} from '@inertiajs/react';
import {
    Check,
    ChevronRight,
    CreditCard,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import StorefrontLayout from '@/layouts/storefront-layout';
import { formatMoney } from '@/lib/money';

type PaymentPageProps = {
    order: {
        public_id: string;
        order_number: string;

        status: string;
        payment_status: string;

        currency: string;
        total_in_cents: number;

        customer_email: string;

        items: {
            id: number;
            name: string;
            quantity: number;
            line_total_in_cents: number;

            components: {
                slot: string;
                slot_label: string;
                name: string;
            }[];
        }[];

        reservation: {
            status: string;
            expires_at: string;
        } | null;
    };
};

export default function PaymentPage({
    order,
}: PaymentPageProps) {
    return (
        <StorefrontLayout>
            <Head
                title={`Payment for ${order.order_number}`}
            />

            <section className="border-b bg-muted/20">
                <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                    <nav className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Link href="/">
                            Home
                        </Link>

                        <ChevronRight className="size-4" />

                        <span aria-current="page">
                            Payment
                        </span>
                    </nav>

                    <Badge
                        variant="outline"
                        className="mt-7 rounded-full"
                    >
                        <Check className="size-3" />
                        Order created
                    </Badge>

                    <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Complete payment
                    </h1>

                    <p className="mt-3 text-sm leading-6 text-muted-foreground">
                        Order {order.order_number} was
                        created and the selected
                        components remain temporarily
                        reserved.
                    </p>
                </div>
            </section>

            <section className="py-12">
                <div className="mx-auto grid max-w-4xl gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:px-8">
                    <div className="rounded-2xl border p-6">
                        <div className="flex items-start gap-4">
                            <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-muted">
                                <CreditCard className="size-5" />
                            </span>

                            <div>
                                <h2 className="font-semibold">
                                    Mollie payment comes
                                    next
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                    The order and inventory
                                    records are now ready.
                                    The next implementation
                                    phase will create the
                                    Mollie payment and
                                    redirect the customer to
                                    Mollie Checkout.
                                </p>
                            </div>
                        </div>

                        <div className="mt-6 rounded-xl border border-dashed p-5 text-sm text-muted-foreground">
                            Do not enable this flow in
                            production until the Mollie
                            payment creation and webhook
                            processing are connected.
                        </div>
                    </div>

                    <aside className="rounded-2xl border p-5">
                        <p className="text-xs text-muted-foreground">
                            Order
                        </p>

                        <p className="mt-1 font-semibold">
                            {order.order_number}
                        </p>

                        <Separator className="my-5" />

                        <div className="flex items-end justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                Total
                            </span>

                            <span className="text-xl font-semibold">
                                {formatMoney(
                                    order.total_in_cents,
                                    order.currency,
                                )}
                            </span>
                        </div>

                        <Button
                            className="mt-6 w-full"
                            disabled
                        >
                            Pay with Mollie
                        </Button>
                    </aside>
                </div>
            </section>
        </StorefrontLayout>
    );
}