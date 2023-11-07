<?php

use Bagisto\MultiSafePay\Controllers\MultiSafePayController;
use Illuminate\Support\Facades\Route;

Route::post('/api/multistafepay/webhook', [MultiSafePayController::class, 'webhook'])->name('multisafepay.webhook');