<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\StaleResourceVersionException;
use App\Http\Requests\Admin\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Services\Inventory\AdminInventorySettingsUpdater;
use Illuminate\Http\RedirectResponse;

class InventorySettingsController extends Controller
{
    public function __invoke(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem, AdminInventorySettingsUpdater $updater): RedirectResponse{
        try{
            $updater->update(
                inventoryItem: $inventoryItem,
                values: [
                    'bin_location' => $request->validated('bin_location'),
                    'safety_stock' => (int) $request->validated('safety_lock'),
                    'reorder_point' => $request->validated('reorder_point') === null ? null : (int) $request->validated('reorder_point'),
                    'is_active' => (bool) $request->validated('is_active')
                ],
                expectedLockVersion: (int) $request->validated('expected_lock_version')
            );
        }catch(StaleResourceVersionException $e){
            return back()->withErrors([
                'inventory'=>$e->getMessage()
            ]);
        }

        return redirect()->route('admin.inventory.show',$inventoryItem)->setStatusCode(303)->with('success','Inventory settings were updated.');
    }
}
