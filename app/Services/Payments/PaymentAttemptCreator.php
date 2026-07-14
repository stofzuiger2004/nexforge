<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

final class PaymentAttemptCreator
{
    public function create(
        Order $order,
    ): Payment {
        return DB::transaction(
            function () use ($order): Payment {
                $lockedOrder = Order::query()
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                if (
                    $lockedOrder->status
                    !== OrderStatus::PendingPayment
                ) {
                    throw new DomainException(
                        'Only an order awaiting payment can create a payment attempt.',
                    );
                }

                if (
                    $lockedOrder->total_in_cents
                    < 1
                ) {
                    throw new DomainException(
                        'The order total must be greater than zero.',
                    );
                }

                /*
                 * Avoid creating another active attempt.
                 */
                $existing = $lockedOrder
                    ->payments()
                    ->whereIn('status', [
                        PaymentStatus::Creating,
                        PaymentStatus::Open,
                        PaymentStatus::Pending,
                        PaymentStatus::Authorized,
                    ])
                    ->latest('attempt_number')
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }

                $attemptNumber =
                    ((int) $lockedOrder
                        ->payments()
                        ->max('attempt_number'))
                    + 1;

                $idempotencyKey = sprintf(
                    'payment:%s:%d',
                    $lockedOrder->public_id,
                    $attemptNumber,
                );

                return $lockedOrder
                    ->payments()
                    ->create([
                        'provider' => PaymentProvider::Mollie,

                        'attempt_number' => $attemptNumber,

                        'status' => PaymentStatus::Creating,

                        'provider_status' => null,

                        'sequence_type' => 'oneoff',

                        'amount_in_cents' => $lockedOrder
                            ->total_in_cents,

                        'currency' => $lockedOrder->currency,

                        'description' => sprintf(
                            'Order %s',
                            $lockedOrder
                                ->order_number,
                        ),

                        'idempotency_key' => $idempotencyKey,

                        'metadata' => [
                        'order_public_id' => $lockedOrder
                            ->public_id,

                        'order_number' => $lockedOrder
                            ->order_number,
                        ],
                    ]);
            },
            attempts: 3,
        );
    }
}
