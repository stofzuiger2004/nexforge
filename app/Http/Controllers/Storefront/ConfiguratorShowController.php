<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ConfigurationStatus;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Services\Configurations\ConfigurationAccessService;
use App\Services\Configurations\ConfiguratorPageDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguratorShowController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Configuration $configuration, ConfigurationAccessService $access, ConfiguratorPageDataService $pageData): Response
    {
        $access->assertCanAccess($request, $configuration);

        if ($configuration->status === ConfigurationStatus::ReadyForCheckout) {
            return to_route('configurator.review.show', $configuration);
        }

        return Inertia::render('storefront/configurator/show', $pageData->build($configuration));
    }
}
