<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\InventoryReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutOrderRequest;
use App\Models\Configuration;
use App\Models\InventoryReservation;
use App\Services\Configurations\ConfigurationAccessService;
use App\Services\Orders\OrderAccessService;
use App\Services\Orders\OrderCreator;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CheckoutOrderController extends Controller
{
    public function __invoke(
        StoreCheckoutOrderRequest $request,
        Configuration $configuration,
        ConfigurationAccessService $configurationAccess,
        OrderCreator $orderCreator,
        OrderAccessService $orderAccess,
    ): RedirectResponse {
        $configurationAccess->assertCanAccess(
            $request,
            $configuration,
        );

        $reservation =
            InventoryReservation::query()
                ->where(
                    'configuration_id',
                    $configuration->id,
                )
                ->where(
                    'configuration_version',
                    $configuration->version,
                )
                ->whereNull('order_id')
                ->where(
                    'status',
                    InventoryReservationStatus
                        ::Active
                        ->value,
                )
                ->latest('id')
                ->first();

        if (
            $reservation === null
            || $reservation->isExpired()
        ) {
            throw ValidationException::withMessages([
                'checkout' =>
                    'The stock reservation expired. Refresh the reservation before continuing.',
            ]);
        }

        try {
            $created =
                $orderCreator
                    ->createFromConfiguration(
                        configuration:
                            $configuration,

                        reservation:
                            $reservation,

                        checkout:
                            $request
                                ->checkoutData(),
                    );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'checkout' =>
                    $exception->getMessage(),
            ]);
        }

        if ($created->guestToken !== null) {
            $orderAccess->rememberGuestToken(
                $request,
                $created->order,
                $created->guestToken,
            );
        }

        return to_route(
            'checkout.payment.show',
            $created->order,
        );
    }
}