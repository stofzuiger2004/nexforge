<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use App\Models\System;
use App\Services\Configurations\ConfigurationAccessService;
use App\Services\Configurations\ConfigurationCreator;
use App\Services\Configurations\ConfigurationValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfiguratorStartController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, System $system, ConfigurationCreator $creator, ConfigurationValidator $validator, ConfigurationAccessService $access): RedirectResponse
    {
        $isAvailable = System::query()->published()->whereKey($system->id)->where('is_configurable', true)->exists();
        abort_unless($isAvailable, 404);

        $priceList = PriceList::query()->where('currency', 'EUR')->default()->available()->firstOrFail();
        abort_unless($system->prices()->where('price_list_id', $priceList->id)->exists(), 404);

        $created = $creator->createFromSystem($system, $priceList, $request->user());

        if ($created->guestToken !== null) {
            $access->rememberGuestToken($request, $created->configuration, $created->guestToken);
        }

        $validator->validate($created->configuration);

        return to_route('configurator.show', $created->configuration);
    }
}
