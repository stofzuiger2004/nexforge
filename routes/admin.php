<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\OrderIndexController;
use App\Http\Controllers\Admin\OrderShowController;
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
        )->name('dashboard');

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
    });
