<?php

declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreComponentRequest;
use App\Services\Catalog\AdminComponentCreator;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ComponentStoreController extends Controller
{
    public function __invoke(StoreComponentRequest $request, AdminComponentCreator $creator): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null,403);

        try{
            $inventoryItem = $creator->create(
                data: $request->validated(),
                image: $request->file('image'),
                actor: $user
            );
        }catch(DomainException $e){
            return back()->withInput()->withErrors([
                'specifications'=>$e->getMessage()
            ]);
        }catch(Throwable $e){
            report($e);
            return back()->withInput()->withErrors([
                'name'=>'The component could not be created.'
            ]);
        }

        return redirect()->route('admin.inventory.show',$inventoryItem)->setStatusCode(303)->with('success','The component was created successfully.');
    }
}
