<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System;
use App\Services\Admin\AdminSystemPresetPageDataService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class SystemEditController extends Controller
{
    public function __invoke(Request $request,System $system,AdminSystemPresetPageDataService $pageData):Response
    {
        return Inertia::render(
            'admin/systems/edit',
            $pageData->build($system,$request)
        );
    }
}
