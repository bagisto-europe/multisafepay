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
        //Route::post('webhook', [MultiSafePayController::class, 'webhook'])->name('multisafepay.webhook');
    });
});
