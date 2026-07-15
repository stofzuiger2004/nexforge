<?php
declare(strict_types=1);
namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\ComponentSlot;
use App\Http\Requests\UpdateConfiguratorComponentRequest;
use App\Models\Configuration;
use App\Models\ProductVariant;
use App\Models\SystemComponent;
use App\Services\Configurations\ConfigurationAccessService;
use App\Services\Configurations\ConfigurationEditor;
use App\Services\Configurations\ConfigurationValidator;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ConfiguratorComponentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateConfiguratorComponentRequest $request,Configuration $configuration,ComponentSlot $slot,ConfigurationAccessService $access,ConfigurationEditor $editor,ConfigurationValidator $validator): RedirectResponse
    {
        if(!$configuration->status->isEditable()){
            abort(409,'This configuratio can no longer be changed.');
        }

        $systemComponent = SystemComponent::query()->where('system_id',$configuration->source_system_id)->where('slot',$slot->value)->orderBy('sort_order')->firstOrFail();

        if(! $systemComponent->is_replaceable){
            throw ValidationException::withMessages([
                'variant_id'=>'This component is fixed for the selected system.'
            ]);
        }

        if ($slot->allowsMultiple()){
            throw ValidationException::withMessages([
                'variant_id'=>'Multiple-selection slots require the quantity interface.'
            ]);
        }

        $validated = $request->validated();
        $variant = ProductVariant::query()->findOrFail($validated['variant_id']);
        $quantity = (int) ($validated['quantity'] ?? 1);
        $currentItem = $configuration->items()->where('slot',$slot->value)->first();

        if($currentItem !== null && $currentItem->product_variant_id === $variant->id && $currentItem->quantity === $quantity){
            return to_route('configurator.show',$configuration);
        }

        try{
            $updatedConfiguration = $editor->setComponent($configuration,$slot,$variant,$quantity);
        } catch(DomainException $e){
            throw ValidationException::withMessages([
                'variant_id'=>$e->getMessage()
            ]);
        }

        $validator->validate($updatedConfiguration);
        return to_route('configurator.show',$configuration)->with('configuration_updated',true);

    }
}
