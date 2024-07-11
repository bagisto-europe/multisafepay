<?php

use Bagisto\MultiSafePay\Controllers\Shop\PayController;
use Illuminate\Support\Facades\Route;

/**
 * Routes for Bagisto MultiSafePay integration.
 *
 * @middleware web, locale, theme, currency
 */
Route::group(['middleware' => ['web', 'locale', 'theme', 'currency']], function () {
    Route::prefix('multisafepay')->group(function () {
        Route::get('order/pay/{id}', [PayController::class, 'register'])->name('shop.customer.order.pay');

        Route::get('order/paid/{transactionid}', [PayController::class, 'success'])->name('shop.customer.order.paid');
    });
});
