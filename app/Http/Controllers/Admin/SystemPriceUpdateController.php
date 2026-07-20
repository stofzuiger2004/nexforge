<?php

declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\StaleResourceVersionException;
use App\Http\Requests\Admin\UpdateSystemPriceRequest;
use App\Models\PriceList;
use App\Models\System;
use App\Services\Pricing\SystemPriceUpdater;
use DomainException;
use Illuminate\Http\RedirectResponse;

final class SystemPriceUpdateController extends Controller
{
    public function __invoke(UpdateSystemPriceRequest $request, System $system, PriceList $priceList, SystemPriceUpdater $updater): RedirectResponse{
        $user = $request->user();
        abort_if($user === null, 403);

        try{
            $updater->update(
                system: $system,
                priceList: $priceList,
                amount: (string) $request->validated('amount'),
                compareAtAmount: $request->validated('compare_at_amount'),
                reason: (string) $request->validated('reason'),
                expectedLockVersion: (int) $request->validated('expected_lock_version'),
                actor: $user
            );
        }catch(StaleResourceVersionException $e){
            return back()->withErrors([
                'expected_lock_version'=>$e->getMessage()
            ]);
        }catch(DomainException $e){
            return back()->withErrors([
                'compare_at_amount'=>$e->getMessage()
            ]);
        }

        return back()->setStatusCode(303)->with('success','The system package price was updated.');
    }
}
