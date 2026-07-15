<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ConfigurationStatus;
use App\Enums\InventoryReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\InventoryReservation;
use App\Services\Configurations\ConfigurationAccessService;
use App\Services\Configurations\ConfigurationReviewPageDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationReviewShowController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Configuration $configuration, ConfigurationAccessService $access, ConfigurationReviewPageDataService $pageData): Response|RedirectResponse
    {
        $access->assertCanAccess($request, $configuration);

        if ($configuration->status !== ConfigurationStatus::ReadyForCheckout) {
            return to_route('configurator.show', $configuration);
        }

        $reservation = InventoryReservation::query()->where('configuration_id', $configuration->id)->where('configuration_version', $configuration->version)->whereNull('order_id')->where('status', InventoryReservationStatus::Active->value)->latest('id')->first();

        if ($reservation === null) {
            return to_route('configurator.show', $configuration)->with('review_error', 'The stock reservation is no longer available.');
        }

        return Inertia::render('storefront/configurator/review', $pageData->build($configuration, $reservation, $request->user()));
    }
}
