<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\StaleResourceVersionException;
use App\Http\Requests\Admin\StoreInventoryAdjustmentRequest;
use App\Models\InventoryItem;
use App\Services\Inventory\AdminInventoryAdjustmentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Throwable;

class InventoryAdjustmentController extends Controller
{
    public function __invoke(StoreInventoryAdjustmentRequest $request, InventoryItem $inventoryItem, AdminInventoryAdjustmentService $service): RedirectResponse{
        $user = $request->user();

        abort_if($user === null, 403);

        try{
            $service->adjust(
                inventoryItem: $inventoryItem,
                type: $request->adjustmentType(),
                quantity: (int) $request->validated('quantity'),
                reason: (string) $request->validated('reason'),
                expectedLockVersion: (int) $request->validated('expected_lock_version'),
                idempotencyKey: (string) $request->validated('idempotency_key'),
                reference: $request->validated('reference'),
                actor: $user
            );
        }catch(StaleResourceVersionException $e){
            return back()->withErrors([
                'expected_lock_version' => $e->getMessage()
            ]);
        }catch(DomainException $e){
            return back()->withErrors([
                'quantity' => $e->getMessage()
            ]);
        }catch(Throwable $e){
            report($e);
            return back()->withErrors([
                'quantity' => 'The inventory adjustment could not be saved.'
            ]);
        }

        return redirect()->route('admin.inventory.show',$inventoryItem)->setStatusCode(303)->with('success','Inventory was adjusted successfully.');
    }
}
