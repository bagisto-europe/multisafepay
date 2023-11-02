<?php

use Illuminate\Support\Facades\Route;
use Bagisto\MultiSafePay\Controllers\OnePageController;


Route::group(['middleware' => ['locale', 'theme', 'currency']], function () {
    /**
     * Checkout routes.
     */
    
     Route::controller(OnepageController::class)->prefix('checkout/onepage')->group(function () {
        Route::get('', 'index')->name('shop.checkout.onepage.index');
        Route::post('save-multipay-gateway', 'storeInSession')->name('shop.checkout.onepage.multipay');
        Route::get('success', 'success')->name('shop.checkout.onepage.success');
    });
        
});