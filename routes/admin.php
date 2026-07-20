<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\OrderIndexController;
use App\Http\Controllers\Admin\OrderShowController;
use App\Http\Controllers\Admin\InventoryAdjustmentController;
use App\Http\Controllers\Admin\InventoryIndexController;
use App\Http\Controllers\Admin\InventorySettingsController;
use App\Http\Controllers\Admin\InventoryShowController;
use App\Http\Controllers\Admin\VariantPriceUpdateController;
use App\Http\Controllers\Admin\ComponentCreateController;
use App\Http\Controllers\Admin\ComponentStoreController;
use App\Models\Order;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware([
        'auth',
        'verified',
        'can:admin.access',
    ])
    ->group(function (): void {
        Route::get(
            '/',
            AdminDashboardController::class,
        )->name('admin');

        Route::get(
            '/orders',
            OrderIndexController::class,
        )
            ->middleware(
                'can:viewAny,'.Order::class,
            )
            ->name('orders.index');

        Route::get(
            '/orders/{order}',
            OrderShowController::class,
        )
            ->middleware(
                'can:view,order',
            )
            ->name('orders.show');
        Route::get('/inventory/components/create',ComponentCreateController::class)->middleware('can:catalog.manage')->name('inventory.components.create');
        Route::post('/inventory/components',ComponentStoreController::class)->middleware('can:catalog.manage')->name('inventory.components.store');
        Route::get('/inventory',InventoryIndexController::class)->middleware('can:inventory.view')->name('inventory.index');
        Route::get('/inventory/{inventoryItem}',InventoryShowController::class)->middleware('can:inventory.view')->name('inventory.show');
        Route::post('/inventory/{inventoryItem}/adjustments',InventoryAdjustmentController::class)->middleware('can:inventory.adjust')->name('inventory.adjustments.store');
        Route::patch('/inventory/{inventoryItem}',InventorySettingsController::class)->middleware('can:inventory.manage')->name('inventory.update');
        Route::patch('/variants/{variant}/prices/{priceList}',VariantPriceUpdateController::class)->middleware('can:prices.manage')->name('variant-prices.update');
    });
