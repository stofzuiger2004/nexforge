<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Services\Configurations\ConfigurationAccessService;
use App\Services\Configurations\ConfigurationReviewService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConfigurationReviewStartController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Configuration $configuration, ConfigurationAccessService $access, ConfigurationReviewService $review): RedirectResponse
    {
        $access->assertCanAccess($request, $configuration);

        try {
            $review->prepare(configuration: $configuration, actor: $request->user());
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['configuration' => $e->getMessage()]);
        }

        return to_route('configurator.review.show', $configuration);
    }
}
