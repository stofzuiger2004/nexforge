<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentProvider;
use App\Enums\PaymentWebhookStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MolliePaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
    ): Response {
        $validated = $request->validate([
            'id' => [
                'required',
                'string',
                'max:120',
            ],
        ]);

        $providerPaymentId =
            $validated['id'];

        $payment = Payment::query()
            ->where(
                'provider',
                PaymentProvider::Mollie,
            )
            ->where(
                'provider_payment_id',
                $providerPaymentId,
            )
            ->first();

        $rawBody = $request->getContent();

        PaymentWebhookEvent::query()->create([
            'provider' =>
                PaymentProvider::Mollie,

            'provider_event_id' => null,
            'event_type' => null,
            'resource_type' => 'payment',

            'provider_resource_id' =>
                $providerPaymentId,

            'payment_id' => $payment?->id,

            'payload' => [
                'form' => $request->all(),
                'content_type' =>
                    $request->header(
                        'Content-Type',
                    ),
            ],

            'payload_hash' => hash(
                'sha256',
                $rawBody !== ''
                    ? $rawBody
                    : json_encode(
                        $request->all(),
                        JSON_THROW_ON_ERROR,
                    ),
            ),

            'processing_status' =>
                PaymentWebhookStatus::Received,

            'attempt_count' => 0,
            'received_at' => now(),
        ]);

        /*
         * The synchronization job will be added next.
         * A successful receipt is acknowledged immediately.
         */
        return response('', 200);
    }
}