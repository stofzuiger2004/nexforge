<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\StaleResourceVersionException;
use App\Http\Requests\Admin\UpdateVariantPriceRequest;
use App\Models\PriceList;
use App\Models\ProductVariant;
use App\Services\Pricing\VariantPriceUpdater;
use DomainException;
use Illuminate\Http\RedirectResponse;

class VariantPriceUpdateController extends Controller
{
    public function __invoke(UpdateVariantPriceRequest $request,ProductVariant $variant,PriceList $priceList,VariantPriceUpdater $updater): RedirectResponse{
        $user = $request->user();

        abort_if($user === null, 403);

        try{
            $updater->update(
                variant: $variant,
                priceList: $priceList,
                amount: (string) $request->validated('amount'),
                compareAtAmount: $request->validated('compare_at_amount'),
                reason: (string) $request->validated('reason'),
                expectedLockVersion: $request->validated('expected_lock_version'),
                actor: $user
            );
        }catch(StaleResourceVersionException $e){
            return back()->withErrors([
                'expected_lock_version' => $e->getMessage()
            ]);
        }catch(DomainException $e){
            return back()->withErrors([
                'compare_at_amount' => $e->getMessage()
            ]);
        }

        return back()->setStatusCode(303)->with('success','The selling price was updated.');
    }
}
