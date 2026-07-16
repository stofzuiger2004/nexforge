<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class AdminOrderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request,
    ): array {
        $latestPayment =
            $this->latestPayment;

        return [
            'public_id' => $this->public_id,

            'order_number' => $this->order_number,

            'href' => route(
                'admin.orders.show',
                $this->resource,
            ),

            'customer' => [
                'name' => $this->user?->name
                    ?? 'Guest customer',

                'email' => $this->customer_email,

                'has_account' => $this->user_id !== null,
            ],

            'status' => $this->status->value,

            'payment_status' => $this
                ->payment_status
                ->value,

            'fulfillment_status' => $this
                ->fulfillment_status
                ->value,

            'currency' => $this->currency,

            'total_in_cents' => $this->total_in_cents,

            'item_count' => (int) (
                $this->items_count
                ?? 0
            ),

            'placed_at' => $this
                ->placed_at
                ?->toIso8601String(),

            'latest_payment' => $latestPayment === null
                    ? null
                    : [
                    'provider' => $latestPayment
                        ->provider
                        ->value,

                    'provider_payment_id' => $latestPayment
                        ->provider_payment_id,

                    'status' => $latestPayment
                        ->status
                        ->value,

                    'method' => $latestPayment
                        ->method,
                ],

            'requires_attention' => $this->requiresAttention(),
        ];
    }

    private function requiresAttention(): bool
    {
        if (
            $this->status
            === OrderStatus::ManualReview
        ) {
            return true;
        }

        if (
            in_array(
                $this->payment_status,
                [
                    OrderPaymentStatus::Failed,
                    OrderPaymentStatus::ChargedBack,
                    OrderPaymentStatus::PartiallyChargedBack,
                ],
                true,
            )
        ) {
            return true;
        }

        return
            $this->payment_status
                === OrderPaymentStatus::Paid
            && $this->fulfillment_status
                === OrderFulfillmentStatus::Unfulfilled;
    }
}
