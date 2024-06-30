<?php

use Bagisto\MultiSafePay\Controllers\Shop\OnePageController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['locale', 'theme', 'currency']], function () {
    /**
     * Checkout routes.
     */
    Route::controller(OnepageController::class)->prefix('checkout/order/onepage')->group(function () {
        Route::get('payment-methods', [OnePageController::class, 'showPaymentMethods'])->name('multisafepay.payment_methods');

        Route::post('save-multipay-gateway', 'storeInSession')->name('shop.checkout.onepage.multipay');

        Route::get('success', 'success')->name('multisafepay.shop.checkout.onepage.success');
    });
});
