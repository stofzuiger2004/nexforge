<?php

declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\ComponentSlot;
use App\Http\Requests\Admin\UpdateSystemDefaultComponentRequest;
use App\Models\ProductVariant;
use App\Models\System;
use App\Services\Systems\SystemDefaultComponentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class SystemDefaultComponentUpdateController extends Controller
{
    public function __invoke(UpdateSystemDefaultComponentRequest $request, System $system,ComponentSlot $slot,SystemDefaultComponentService $service): RedirectResponse{
        $validated = $request->validated();
        $variant = ProductVariant::query()->findOrFail($validated['variant_id']);

        try{
            $service->assign(
                system: $system,
                slot: $slot,
                variant: $variant,
                quantity: (int) $validated['quantity'],
                isRequired: (bool) $validated['is_required'],
                isReplaceable: (bool) $validated['is_replaceable']
            );
        }catch(DomainException $e){
            throw ValidationException::withMessages(['variant_id'=>$e->getMessage()]);
        }

        return back(status: 303)->with('success',sprintf('The default %s for %s was updated.',$slot->label(),$system->name));
    }
}
