<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\System;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $featuredSystems = System::query()
            ->where('is_active',true)
            ->where('is_featured',true)
            ->orderBy('price_in_cents')
            ->limit(3)
            ->get([
                'id',
                'name',
                'description',
                'slug',
                'processor',
                'graphics_card',
                'memory',
                'storage',
                'price_in_cents',
                'image_path'
            ]);

        return Inertia::render('storefront/index',['featuredSystems'=>$featuredSystems]);
    }
}
