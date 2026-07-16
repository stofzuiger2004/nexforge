<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class AdminOrderDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request,
    ): array {
        $user = $request->user();

        $canViewCustomerData =
            $user?->can(
                'orders.viewCustomerData',
            ) ?? false;

        $canViewPayments =
            $user?->can(
                'payments.view',
            ) ?? false;

        $canViewInventory =
            $user?->can(
                'inventory.view',
            ) ?? false;

        return [
            'public_id' => $this->public_id,

            'order_number' => $this->order_number,

            'status' => $this->status->value,

            'payment_status' => $this
                ->payment_status
                ->value,

            'fulfillment_status' => $this
                ->fulfillment_status
                ->value,

            'customer' => [
                'account_id' => $this->user?->id,

                'name' => $this->user?->name
                    ?? 'Guest customer',

                'email' => $this->customer_email,

                'locale' => $this->customer_locale,

                'has_account' => $this->user_id !== null,
            ],

            'pricing' => [
                'currency' => $this->currency,

                'subtotal_in_cents' => $this
                    ->subtotal_in_cents,

                'adjustment_total_in_cents' => $this
                    ->adjustment_total_in_cents,

                'shipping_in_cents' => $this
                    ->shipping_in_cents,

                'tax_in_cents' => $this
                    ->tax_in_cents,

                'total_in_cents' => $this
                    ->total_in_cents,

                'paid_in_cents' => $this
                    ->paid_in_cents,

                'refunded_in_cents' => $this
                    ->refunded_in_cents,

                'charged_back_in_cents' => $this
                    ->charged_back_in_cents,

                'outstanding_in_cents' => max(
                    0,
                    $this->total_in_cents
                        - $this
                            ->paid_in_cents,
                ),
            ],

            'dates' => [
                'created_at' => $this
                ->created_at
                ->toIso8601String(),

                'placed_at' => $this
                ->placed_at
                ?->toIso8601String(),

                'paid_at' => $this
                ->paid_at
                ?->toIso8601String(),

                'cancelled_at' => $this
                ->cancelled_at
                ?->toIso8601String(),

                'completed_at' => $this
                ->completed_at
                ?->toIso8601String(),
            ],

            'addresses' => $canViewCustomerData
                    ? $this->addressData()
                    : null,

            'items' => $this->items
                ->map(
                    static fn (
                        $item,
                    ): array => [
                        'id' => $item->id,

                        'line_number' => $item
                            ->line_number,

                        'type' => $item
                            ->type
                            ->value,

                        'sku' => $item
                            ->sku_snapshot,

                        'name' => $item
                            ->name_snapshot,

                        'description' => $item
                            ->description_snapshot,

                        'quantity' => $item->quantity,

                        'unit_price_in_cents' => $item
                            ->unit_price_in_cents,

                        'subtotal_in_cents' => $item
                            ->subtotal_in_cents,

                        'adjustment_total_in_cents' => $item
                            ->adjustment_total_in_cents,

                        'tax_in_cents' => $item
                            ->tax_in_cents,

                        'line_total_in_cents' => $item
                            ->line_total_in_cents,

                        'configuration' => [
                            'public_id' => $item
                                ->sourceConfiguration
                                ?->public_id,

                            'version' => $item
                                ->source_configuration_version,
                        ],

                        'components' => $item
                            ->components
                            ->map(
                                static fn (
                                    $component,
                                ): array => [
                                    'id' => $component
                                        ->id,

                                    'slot' => $component
                                        ->slot
                                        ->value,

                                    'slot_label' => $component
                                        ->slot
                                        ->label(),

                                    'sku' => $component
                                        ->sku_snapshot,

                                    'name' => $component
                                        ->name_snapshot,

                                    'quantity' => $component
                                        ->quantity,

                                    'unit_price_in_cents' => $component
                                        ->unit_price_in_cents,

                                    'line_total_in_cents' => $component
                                        ->line_total_in_cents,
                                ],
                            )
                            ->values()
                            ->all(),
                    ],
                )
                ->values()
                ->all(),

            'adjustments' => $this->adjustments
                ->map(
                    static fn (
                        $adjustment,
                    ): array => [
                        'id' => $adjustment->id,

                        'type' => $adjustment
                            ->type
                            ->value,

                        'code' => $adjustment
                            ->code,

                        'label' => $adjustment
                            ->label,

                        'amount_in_cents' => $adjustment
                            ->amount_in_cents,

                        'tax_in_cents' => $adjustment
                            ->tax_in_cents,
                    ],
                )
                ->values()
                ->all(),

            'payments' => $canViewPayments
                    ? $this->paymentData()
                    : null,

            'inventory_reservations' => $canViewInventory
                    ? $this
                        ->reservationData()
                    : null,

            'timeline' => $this->statusHistory
                ->sortByDesc('id')
                ->map(
                    static fn (
                        $history,
                    ): array => [
                        'id' => $history->id,

                        'category' => $history
                            ->category
                            ->value,

                        'from_status' => $history
                            ->from_status,

                        'to_status' => $history
                            ->to_status,

                        'reason' => $history
                            ->reason,

                        'actor' => $history
                            ->actor
                            ?->name,

                        'created_at' => $history
                            ->created_at
                            ->toIso8601String(),
                    ],
                )
                ->values()
                ->all(),

            /*
             * Only known safe metadata keys are exposed.
             */
            'references' => [
                'configuration_public_id' => data_get(
                    $this->metadata,
                    'configuration_public_id',
                ),

                'configuration_version' => data_get(
                    $this->metadata,
                    'configuration_version',
                ),

                'reservation_public_id' => data_get(
                    $this->metadata,
                    'reservation_public_id',
                ),

                'terms_version' => data_get(
                    $this->metadata,
                    'terms_version',
                ),

                'terms_accepted_at' => data_get(
                    $this->metadata,
                    'terms_accepted_at',
                ),
            ],

            'can' => [
                'view_customer_data' => $canViewCustomerData,

                'view_payments' => $canViewPayments,

                'view_inventory' => $canViewInventory,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function addressData(): array
    {
        return $this->addresses
            ->map(
                static fn (
                    $address,
                ): array => [
                    'id' => $address->id,

                    'type' => $address
                        ->type
                        ->value,

                    'first_name' => $address
                        ->first_name,

                    'last_name' => $address
                        ->last_name,

                    'company' => $address
                        ->company,

                    'vat_number' => $address
                        ->vat_number,

                    'address_line_1' => $address
                        ->address_line_1,

                    'address_line_2' => $address
                        ->address_line_2,

                    'postal_code' => $address
                        ->postal_code,

                    'city' => $address->city,

                    'state' => $address->state,

                    'country_code' => $address
                        ->country_code,

                    'email' => $address->email,

                    'phone' => $address->phone,
                ],
            )
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentData(): array
    {
        return $this->payments
            ->map(
                static fn (
                    $payment,
                ): array => [
                    'public_id' => $payment
                        ->public_id,

                    'attempt_number' => $payment
                        ->attempt_number,

                    'provider' => $payment
                        ->provider
                        ->value,

                    'provider_payment_id' => $payment
                        ->provider_payment_id,

                    'provider_profile_id' => $payment
                        ->provider_profile_id,

                    'status' => $payment
                        ->status
                        ->value,

                    'provider_status' => $payment
                        ->provider_status,

                    'mode' => $payment
                        ->mode
                        ?->value,

                    'method' => $payment
                        ->method,

                    'amount_in_cents' => $payment
                        ->amount_in_cents,

                    'currency' => $payment
                        ->currency,

                    'failure_code' => $payment
                        ->failure_code,

                    'failure_message' => $payment
                        ->failure_message,

                    'webhook_event_count' => (int) (
                        $payment
                            ->webhook_events_count
                        ?? 0
                    ),

                    'dates' => [
                    'created_at' => $payment
                        ->created_at
                        ->toIso8601String(),

                    'provider_created_at' => $payment
                        ->provider_created_at
                        ?->toIso8601String(),

                    'authorized_at' => $payment
                        ->authorized_at
                        ?->toIso8601String(),

                    'paid_at' => $payment
                        ->paid_at
                        ?->toIso8601String(),

                    'failed_at' => $payment
                        ->failed_at
                        ?->toIso8601String(),

                    'cancelled_at' => $payment
                        ->cancelled_at
                        ?->toIso8601String(),

                    'expired_at' => $payment
                        ->expired_at
                        ?->toIso8601String(),

                    'last_synced_at' => $payment
                        ->last_synced_at
                        ?->toIso8601String(),
                    ],

                    'refunds' => $payment
                        ->refunds
                        ->map(
                            static fn (
                                $refund,
                            ): array => [
                                'public_id' => $refund
                                    ->public_id,

                                'provider_refund_id' => $refund
                                    ->provider_refund_id,

                                'status' => $refund
                                    ->status
                                    ->value,

                                'amount_in_cents' => $refund
                                    ->amount_in_cents,

                                'currency' => $refund
                                    ->currency,

                                'reason' => $refund
                                    ->reason,

                                'created_at' => $refund
                                    ->created_at
                                    ->toIso8601String(),
                            ],
                        )
                        ->values()
                        ->all(),

                    'chargebacks' => $payment
                        ->chargebacks
                        ->map(
                            static fn (
                                $chargeback,
                            ): array => [
                                'public_id' => $chargeback
                                    ->public_id,

                                'provider_chargeback_id' => $chargeback
                                    ->provider_chargeback_id,

                                'status' => $chargeback
                                    ->status
                                    ->value,

                                'amount_in_cents' => $chargeback
                                    ->amount_in_cents,

                                'currency' => $chargeback
                                    ->currency,

                                'reason_code' => $chargeback
                                    ->reason_code,

                                'reason_description' => $chargeback
                                    ->reason_description,

                                'provider_created_at' => $chargeback
                                    ->provider_created_at
                                    ->toIso8601String(),
                            ],
                        )
                        ->values()
                        ->all(),
                ],
            )
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function reservationData(): array
    {
        return $this
            ->inventoryReservations
            ->map(
                static fn (
                    $reservation,
                ): array => [
                    'public_id' => $reservation
                        ->public_id,

                    'status' => $reservation
                        ->status
                        ->value,

                    'configuration_version' => $reservation
                        ->configuration_version,

                    'warehouse' => [
                        'code' => $reservation
                            ->warehouse
                            ->code,

                        'name' => $reservation
                            ->warehouse
                            ->name,
                    ],

                    'reserved_at' => $reservation
                        ->reserved_at
                        ->toIso8601String(),

                    'expires_at' => $reservation
                        ->expires_at
                        ->toIso8601String(),

                    'committed_at' => $reservation
                        ->committed_at
                        ?->toIso8601String(),

                    'released_at' => $reservation
                        ->released_at
                        ?->toIso8601String(),

                    'consumed_at' => $reservation
                        ->consumed_at
                        ?->toIso8601String(),

                    'items' => $reservation
                        ->items
                        ->map(
                            static fn (
                                $item,
                            ): array => [
                                'id' => $item->id,

                                'sku' => $item
                                    ->sku_snapshot,

                                'name' => $item
                                    ->name_snapshot,

                                'quantity' => $item
                                    ->quantity,

                                'released_quantity' => $item
                                    ->released_quantity,

                                'consumed_quantity' => $item
                                    ->consumed_quantity,

                                'outstanding_quantity' => $item
                                    ->outstandingQuantity(),

                                'inventory_item_id' => $item
                                    ->inventory_item_id,
                            ],
                        )
                        ->values()
                        ->all(),
                ],
            )
            ->values()
            ->all();
    }
}
