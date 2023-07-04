<?php

use Illuminate\Support\Facades\Route;
use Bagisto\MultiSafePay\Controllers\MultiSafePayController;
use Bagisto\MultiSafePay\Controllers\OnePageController;

/**
 * Routes for Bagisto MultiSafePay integration.
 *
 * @middleware web, locale, theme, currency
 */

 Route::group(['middleware' => ['web','locale', 'theme', 'currency']], function () {
    Route::prefix('multisafepay')->group(function () {
        Route::get('payment-methods', [MultiSafePayController::class, 'showPaymentMethods'])->name('multisafepay.payment_methods');
        Route::get('webhook/{orderId}', [MultiSafePayController::class, 'webhook'])->name('multisafepay.webhook');
    });

    Route::get('checkout/success', [OnePageController::class, 'success'])->defaults('_config', [
        'view' => 'shop::checkout.success',
    ])->name('shop.checkout.success');
});
