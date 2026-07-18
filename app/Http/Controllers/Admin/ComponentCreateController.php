<?php

declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminComponentFormDataService;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ComponentCreateController extends Controller
{
    public function __invoke(AdminComponentFormDataService $formData): Response
    {
        return Inertia::render('admin/inventory/components/create',['options'=>$formData->all(),'formTokens'=>['creation'=>(string) Str::ulid()]]);
    }
}
