<?php

use Bagisto\MultiSafePay\Controllers\API\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/api/multistafepay/webhook', [WebhookController::class, 'handle'])->name('shop.api.multisafepay.webhook');
