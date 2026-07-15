<?php

use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Webhooks\MolliePaymentWebhookController;
use App\Http\Controllers\Storefront\ConfiguratorComponentController;
use App\Http\Controllers\Storefront\ConfiguratorShowController;
use App\Http\Controllers\Storefront\ConfiguratorStartController;
use App\Http\Controllers\Storefront\SystemShowController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/gaming-pcs/{slug}',SystemShowController::class)->where('slug','[a-z0-9-]+')->name('gaming-pcs.show');

Route::redirect('/configure','/gaming-pcs')->name('configurator.index');

Route::post('/configure/start/{system}',ConfiguratorStartController::class)->where('system','[a-z0-9-]+')->name('configurator.start');

Route::get('/configure/{configuration}',ConfiguratorShowController::class)->whereUlid('configuration')->name('configurator.show');

Route::patch('/configure/{configuration}/components/{slot}',ConfiguratorComponentController::class)->whereUlid('configuration')->name('configurator.components.update');

Route::inertia('/gaming-pcs', 'coming-soon', [
    'title' => 'Gaming PCs',
])->name('gaming-pcs.index');

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
