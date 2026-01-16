<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::post('webhooks/paddle', [PaymentController::class, 'paddleWebhook'])->name('webhooks.paddle');
