<?php

require __DIR__.'/admin.php';
use App\Http\Controllers\Storefront\CheckoutOrderController;
use App\Http\Controllers\Storefront\ConfigurationReviewDestroyController;
use App\Http\Controllers\Storefront\ConfigurationReviewShowController;
use App\Http\Controllers\Storefront\ConfigurationReviewStartController;
use App\Http\Controllers\Storefront\ConfiguratorComponentController;
use App\Http\Controllers\Storefront\ConfiguratorShowController;
use App\Http\Controllers\Storefront\ConfiguratorStartController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OrderPaymentShowController;
use App\Http\Controllers\Storefront\SystemShowController;
use App\Http\Controllers\Webhooks\MolliePaymentWebhookController;
use App\Http\Controllers\Storefront\SystemIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/gaming-pcs/{slug}', SystemShowController::class)->where('slug', '[a-z0-9-]+')->name('gaming-pcs.show');

Route::post('/configure/start/{system}', ConfiguratorStartController::class)->where('system', '[a-z0-9-]+')->name('configurator.start');

Route::get('/configure/{configuration}', ConfiguratorShowController::class)->whereUlid('configuration')->name('configurator.show');

Route::patch('/configure/{configuration}/components/{slot}', ConfiguratorComponentController::class)->whereUlid('configuration')->name('configurator.components.update');

Route::post('/configure/{configuration}/review', ConfigurationReviewStartController::class)->whereUlid('configuration')->name('configurator.review.start');

Route::get('/configure/{configuration}/review', ConfigurationReviewShowController::class)->whereUlid('configuration')->name('configurator.review.show');

Route::delete('/configure/{configuration}/review', ConfigurationReviewDestroyController::class)->whereUlid('configuration')->name('configurator.review.destroy');

Route::post('/configure/{configuration}/order', CheckoutOrderController::class)->whereUlid('configuration')->name('checkout.orders.store');

Route::get('/orders/{order}/payment', OrderPaymentShowController::class)->whereUlid('order')->name('checkout.payment.show');

Route::get('/gaming-pcs',SystemIndexController::class)->name('gaming-pcs.index');

Route::get('/configure',SystemIndexController::class)->name('configurator.index');

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
