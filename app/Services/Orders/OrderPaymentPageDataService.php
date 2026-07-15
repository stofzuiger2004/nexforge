<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;

final class OrderPaymentPageDataService
{
    /**
     * @return array<string, mixed>
     */
    public function build(
        Order $order,
    ): array {
        $order->load([
            'items.components',

            'inventoryReservations' => static fn ($query) => $query
                ->latest('id'),
        ]);

        $reservation =
            $order
                ->inventoryReservations
                ->first();

        return [
            'order' => [
                'public_id' => $order->public_id,

                'order_number' => $order->order_number,

                'status' => $order->status->value,

                'payment_status' => $order
                    ->payment_status
                    ->value,

                'currency' => $order->currency,

                'total_in_cents' => $order
                    ->total_in_cents,

                'customer_email' => $order
                    ->customer_email,

                'items' => $order
                    ->items
                    ->map(
                        static fn ($item): array => [
                            'id' => $item->id,

                            'name' => $item
                                ->name_snapshot,

                            'quantity' => $item->quantity,

                            'line_total_in_cents' => $item
                                ->line_total_in_cents,

                            'components' => $item
                                ->components
                                ->map(
                                    static fn (
                                        $component,
                                    ): array => [
                                        'slot' => $component
                                            ->slot
                                            ->value,

                                        'slot_label' => $component
                                            ->slot
                                            ->label(),

                                        'name' => $component
                                            ->name_snapshot,
                                    ],
                                )
                                ->values()
                                ->all(),
                        ],
                    )
                    ->values()
                    ->all(),

                'reservation' => $reservation === null
                        ? null
                        : [
                            'status' => $reservation
                                ->status
                                ->value,

                            'expires_at' => $reservation
                                ->expires_at
                                ->toIso8601String(),
                        ],
            ],
        ];
    }
}
