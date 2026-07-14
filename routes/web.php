<?php

use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Webhooks\MolliePaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::inertia('/gaming-pcs', 'coming-soon', [
    'title' => 'Gaming PCs',
])->name('gaming-pcs.index');

Route::inertia('/configure', 'coming-soon', [
    'title' => 'PC Configurator',
])->name('configurator.index');

Route::inertia('/components', 'coming-soon', [
    'title' => 'Components',
])->name('components.index');

Route::inertia('/support', 'coming-soon', [
    'title' => 'Support',
])->name('support');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/webhooks/mollie/payments', MolliePaymentWebhookController::class)->name('webhooks.mollie.payments');

require __DIR__.'/settings.php';
