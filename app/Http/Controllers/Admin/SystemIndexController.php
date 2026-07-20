<?php

declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Admin\AdminSystemListResource;
use App\Models\System;
use Inertia\Inertia;
use Inertia\Response;

class SystemIndexController extends Controller
{
    public function __invoke(Request $request):Response
    {
        $systems = System::query()->withCount('components')->with('prices.priceList')->orderBy('sort_order')->orderBy('name')->paginate(25)->withQueryString();

        return Inertia::render('admin/systems/index',[
            'systems'=>[
                'data'=>AdminSystemListResource::collection($systems->getCollection())->resolve($request),
                'meta'=>[
                    'current_page'=>$systems->currentPage(),
                    'from'=>$systems->firstItem(),
                    'last_page'=>$systems->lastPage(),
                    'per_page'=>$systems->perPage(),
                    'to'=>$systems->lastItem(),
                    'total'=>$systems->total(),
                    'links'=>$systems->linkCollection()->map(static fn (array $link): array => [
                        'url'=>$link['url'],
                        'label'=>$link['label'],
                        'active'=>$link['active']
                    ])->values()->all()
                ]
            ]
        ]);
    }
}
