<?php

use Illuminate\Support\Facades\Route;
use Bagisto\MultiSafePay\Controllers\OnePageController;

Route::group(['middleware' => ['locale', 'theme', 'currency']], function () {
    /**
     * Checkout routes.
     */

    Route::get('checkout/success', [OnePageController::class, 'success'])->name('shop.checkout.success');
    
});