<?php

declare(strict_types=1);
namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Storefront\FeaturedSystemResource;
use App\Models\PriceList;
use App\Models\System;
use App\Services\Storefront\SystemAvailabilityService;
use Inertia\Inertia;
use Inertia\Response;

class SystemIndexController extends Controller
{
    public function __invoke(Request $request, SystemAvailabilityService $availability): Response
    {
        $priceList = PriceList::query()->where('currency','EUR')->default()->available()->firstOrFail();
        
        $systems = System::query()->published()->where('is_configurable',true)
            ->whereHas('prices', static fn ($query)=>$query->where('price_list_id',$priceList->id))
            ->with(
                ['prices'=>static fn ($query)=>$query->where('price_list_id',$priceList->id),
                'prices.priceList',
                'images'=>static fn ($query)=>$query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                'components'=>static fn($query)=>$query->orderBy('sort_order')->orderBy('id'),
                'components.variant.product.brand',
                'components.variant.inventoryItems'=>static fn($query)=>$query->where('is_active',true),
                'components.variant.inventoryItems.warehouse'                
                ])->orderBy('sort_order')->orderBy('name')->get();
        
                $systems->each(
                    static function(System $system) use ($availability): void{
                        $system->setAttribute('available-builds',$availability->availableBuilds($system));
                    }
                );

                $context = $request->routeIs('configurator.index') ? 'configurator' : 'catalogue';

                return Inertia::render('storefront/systems/index',[
                    'systems'=>FeaturedSystemResource::collection($systems)->resolve($request),
                    'page'=>$this->pageContent($context)
                ]);
    }

    private function pageContent(string $context): array{
        if($context === 'configurator'){
            return [
                'context' => 'configurator',
                'eyebrow' => 'PC configurator',
                'title' => 'Choose your starting configuration',
                'description' => 'Select a balanced gaming PC preset, then adjust the processor, graphics card, memory, storage, case, cooling, and other components.'
            ];
        }
        return [
            'context' => 'catalogue',
            'eyebrow' => 'Gaming PCs',
            'title' => 'Gaming systems built around your performance goals',
            'description' => 'Compare our base systems, review their core components, and use any preset as the foundation for your custom gaming PC.'
        ];
    }
}
