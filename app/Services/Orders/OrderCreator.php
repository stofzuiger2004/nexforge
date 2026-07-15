<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Data\Checkout\CheckoutData;
use App\Data\Checkout\CreatedOrder;
use App\Enums\ConfigurationAdjustmentType;
use App\Enums\ConfigurationStatus;
use App\Enums\InventoryReservationStatus;
use App\Enums\OrderAddressType;
use App\Enums\OrderAdjustmentType;
use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderStatusCategory;
use App\Models\Configuration;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\Inventory\InventoryReservationService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrderCreator
{
    public function __construct(
        private readonly InventoryReservationService $inventoryReservations,
    ) {}

    public function createFromConfiguration(
        Configuration $configuration,
        InventoryReservation $reservation,
        CheckoutData $checkout,
    ): CreatedOrder {
        return DB::transaction(
            function () use (
                $configuration,
                $reservation,
                $checkout,
            ): CreatedOrder {
                $lockedConfiguration =
                    Configuration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $configuration->id,
                        );

                if (! $checkout->termsAccepted) {
                    throw new DomainException('The checkout terms must be accepted.');
                }

                $lockedReservation =
                    InventoryReservation::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $reservation->id,
                        );

                $this->assertCheckoutIsValid(
                    $lockedConfiguration,
                    $lockedReservation,
                );

                $lockedConfiguration->load([
                    'sourceSystem',

                    'items.variant.product.category',

                    'items.variant'
                        .'.specificationValues.specification',

                    'items.variant'
                        .'.specificationOptions.specification',

                    'adjustments',
                ]);

                $publicId =
                    (string) Str::ulid();

                $prefix = strtoupper(
                    (string) config(
                        'checkout.order_prefix',
                        'NF',
                    ),
                );

                $orderNumber = sprintf(
                    '%s-%s',
                    $prefix,
                    $publicId,
                );

                $guestToken =
                    $lockedConfiguration->user_id === null
                        ? Str::random(64)
                        : null;

                $shippingInCents =
                    $checkout->shippingInCents;

                if ($shippingInCents < 0) {
                    throw new DomainException(
                        'Shipping may not be negative.',
                    );
                }

                $totalInCents =
                    $lockedConfiguration->total_in_cents
                    + $shippingInCents;

                $order = Order::query()->create([
                    'public_id' => $publicId,
                    'order_number' => $orderNumber,

                    'user_id' => $lockedConfiguration->user_id,

                    'guest_token_hash' => $guestToken === null
                            ? null
                            : hash(
                                'sha256',
                                $guestToken,
                            ),

                    'status' => OrderStatus::PendingPayment,

                    'payment_status' => OrderPaymentStatus::Unpaid,

                    'fulfillment_status' => OrderFulfillmentStatus::StockAllocated,

                    'customer_email' => $checkout->email,

                    'customer_locale' => $checkout->locale,

                    'currency' => $lockedConfiguration->currency,

                    'subtotal_in_cents' => $lockedConfiguration
                        ->subtotal_in_cents,

                    'adjustment_total_in_cents' => $lockedConfiguration
                        ->adjustment_total_in_cents,

                    'shipping_in_cents' => $shippingInCents,

                    'tax_in_cents' => $lockedConfiguration
                        ->tax_in_cents,

                    'total_in_cents' => $totalInCents,

                    'paid_in_cents' => 0,
                    'refunded_in_cents' => 0,
                    'charged_back_in_cents' => 0,

                    'placed_at' => now(),

                    'metadata' => [
                        'configuration_public_id' => $lockedConfiguration
                            ->public_id,

                        'configuration_version' => $lockedConfiguration
                            ->version,

                        'reservation_public_id' => $lockedReservation
                            ->public_id,
                        'terms_version' => config('checkout.terms_version'),
                        'terms_accepted_at' => now()->toIso8601String(),
                    ],
                ]);

                $orderItem =
                    $this->createConfiguredSystemItem(
                        $order,
                        $lockedConfiguration,
                    );

                $this->copyComponents(
                    $orderItem,
                    $lockedConfiguration,
                );

                $this->copyAddresses(
                    $order,
                    $checkout,
                );

                $this->copyAdjustments(
                    $order,
                    $orderItem,
                    $lockedConfiguration,
                    $shippingInCents,
                );

                $this->inventoryReservations
                    ->attachToOrder(
                        $lockedReservation,
                        $order,
                    );

                $lockedConfiguration->forceFill([
                    'status' => ConfigurationStatus::Converted,

                    'last_activity_at' => now(),
                ])->save();

                $this->createInitialHistory($order);

                return new CreatedOrder(
                    $order->fresh([
                        'items.components',
                        'addresses',
                        'adjustments',
                        'inventoryReservations',
                    ]),
                    $guestToken,
                );
            },
            attempts: 3,
        );
    }

    private function assertCheckoutIsValid(
        Configuration $configuration,
        InventoryReservation $reservation,
    ): void {
        if (
            $configuration->status
            !== ConfigurationStatus::ReadyForCheckout
        ) {
            throw new DomainException(
                'Only a reviewed configuration can become an order.',
            );
        }

        if (
            $reservation->status
            !== InventoryReservationStatus::Active
        ) {
            throw new DomainException(
                'The inventory reservation is not active.',
            );
        }

        if ($reservation->isExpired()) {
            throw new DomainException(
                'The inventory reservation has expired.',
            );
        }

        if (
            $reservation->configuration_id
            !== $configuration->id
        ) {
            throw new DomainException(
                'The reservation belongs to another configuration.',
            );
        }

        if (
            $reservation->configuration_version
            !== $configuration->version
        ) {
            throw new DomainException(
                'The reservation belongs to an older configuration version.',
            );
        }

        if ($reservation->order_id !== null) {
            throw new DomainException(
                'The reservation already belongs to an order.',
            );
        }
    }

    private function createConfiguredSystemItem(
        Order $order,
        Configuration $configuration,
    ): OrderItem {
        $name =
            $configuration->name
            ?? $configuration
                ->sourceSystem
                ?->name
            ?? 'Custom Gaming PC';

        return $order->items()->create([
            'source_configuration_id' => $configuration->id,

            'source_configuration_version' => $configuration->version,

            'product_variant_id' => null,
            'line_number' => 1,

            'type' => OrderItemType::ConfiguredSystem,

            'sku_snapshot' => $configuration
                ->sourceSystem
                ?->sku,

            'name_snapshot' => $name,

            'description_snapshot' => $configuration
                ->sourceSystem
                ?->short_description,

            'quantity' => 1,

            'unit_price_in_cents' => $configuration
                ->subtotal_in_cents,

            'subtotal_in_cents' => $configuration
                ->subtotal_in_cents,

            'adjustment_total_in_cents' => $configuration
                ->adjustment_total_in_cents,

            'tax_in_cents' => $configuration
                ->tax_in_cents,

            'line_total_in_cents' => $configuration
                ->total_in_cents,

            'configuration_snapshot' => [
                'public_id' => $configuration->public_id,

                'version' => $configuration->version,

                'source_system_id' => $configuration
                    ->source_system_id,

                'source_system_sku' => $configuration
                    ->sourceSystem
                    ?->sku,

                'name' => $name,
            ],
        ]);
    }

    private function copyComponents(
        OrderItem $orderItem,
        Configuration $configuration,
    ): void {
        $lineNumber = 1;

        foreach (
            $configuration->items as $configurationItem
        ) {
            $orderItem->components()->create([
                'source_configuration_item_id' => $configurationItem->id,

                'product_variant_id' => $configurationItem
                    ->product_variant_id,

                'line_number' => $lineNumber,

                'slot' => $configurationItem->slot,

                'sku_snapshot' => $configurationItem
                    ->sku_snapshot,

                'name_snapshot' => $configurationItem
                    ->name_snapshot,

                'quantity' => $configurationItem
                    ->quantity,

                'unit_price_in_cents' => $configurationItem
                    ->unit_price_in_cents,

                'line_total_in_cents' => $configurationItem
                    ->line_total_in_cents,

                'specifications_snapshot' => $this->specificationSnapshot(
                    $configurationItem->variant,
                ),
            ]);

            $lineNumber++;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function specificationSnapshot(
        ProductVariant $variant,
    ): array {
        $snapshot = [];

        foreach (
            $variant->specificationValues as $value
        ) {
            $specification =
                $value->specification;

            $scalarValue = match (true) {
                $value->value_text !== null => $value->value_text,

                $value->value_integer !== null => $value->value_integer,

                $value->value_decimal !== null => $value->value_decimal,

                $value->value_boolean !== null => $value->value_boolean,

                default => null,
            };

            $snapshot[$specification->key] = [
                'name' => $specification->name,
                'value' => $scalarValue,
                'unit' => $specification->unit,
            ];
        }

        foreach (
            $variant
                ->specificationOptions
                ->groupBy('specification_id') as $options
        ) {
            $first = $options->first();

            $snapshot[
                $first->specification->key
            ] = [
                'name' => $first
                    ->specification
                    ->name,

                'values' => $options
                    ->pluck('label')
                    ->values()
                    ->all(),

                'unit' => null,
            ];
        }

        return $snapshot;
    }

    private function copyAddresses(
        Order $order,
        CheckoutData $checkout,
    ): void {
        $order->addresses()->create(
            $checkout
                ->billingAddress
                ->toOrderAttributes(
                    OrderAddressType::Billing,
                ),
        );

        $order->addresses()->create(
            $checkout
                ->shippingAddress
                ->toOrderAttributes(
                    OrderAddressType::Shipping,
                ),
        );
    }

    private function copyAdjustments(
        Order $order,
        OrderItem $orderItem,
        Configuration $configuration,
        int $shippingInCents,
    ): void {
        $lineNumber = 1;

        foreach (
            $configuration->adjustments as $adjustment
        ) {
            $order->adjustments()->create([
                'order_item_id' => $orderItem->id,

                'line_number' => $lineNumber,

                'type' => $this->mapAdjustmentType(
                    $adjustment->type,
                ),

                'code' => $adjustment->code,

                'label' => $adjustment->label,

                'amount_in_cents' => $adjustment
                    ->amount_in_cents,

                'tax_in_cents' => 0,

                'metadata' => $adjustment->metadata,
            ]);

            $lineNumber++;
        }

        if ($shippingInCents > 0) {
            $order->adjustments()->create([
                'order_item_id' => null,
                'line_number' => $lineNumber,

                'type' => OrderAdjustmentType::Shipping,

                'code' => 'shipping',
                'label' => 'Shipping',

                'amount_in_cents' => $shippingInCents,

                'tax_in_cents' => 0,
            ]);
        }
    }

    private function mapAdjustmentType(
        ConfigurationAdjustmentType $type,
    ): OrderAdjustmentType {
        return match ($type) {
            ConfigurationAdjustmentType::Package => OrderAdjustmentType::Package,

            ConfigurationAdjustmentType::Fee => OrderAdjustmentType::Fee,

            ConfigurationAdjustmentType::Discount => OrderAdjustmentType::Discount,

            ConfigurationAdjustmentType::Manual => OrderAdjustmentType::Manual,
        };
    }

    private function createInitialHistory(
        Order $order,
    ): void {
        $order->statusHistory()->createMany([
            [
                'category' => OrderStatusCategory::Order,

                'from_status' => null,

                'to_status' => OrderStatus::PendingPayment
                    ->value,

                'reason' => 'Order created from a validated configuration.',
            ],

            [
                'category' => OrderStatusCategory::Payment,

                'from_status' => null,

                'to_status' => OrderPaymentStatus::Unpaid
                    ->value,

                'reason' => 'No payment has been completed.',
            ],

            [
                'category' => OrderStatusCategory::Fulfillment,

                'from_status' => null,

                'to_status' => OrderFulfillmentStatus::StockAllocated
                    ->value,

                'reason' => 'Inventory reservation attached to the order.',
            ],
        ]);
    }
}
