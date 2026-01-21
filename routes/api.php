<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\DisputeController;

Route::post('webhooks/paddle', [PaymentController::class, 'paddleWebhook'])->name('webhooks.paddle');

// Escrow Routes
Route::middleware('auth:sanctum')->prefix('escrow')->group(function () {
    Route::post('orders/{order}/hold', [EscrowController::class, 'holdFunds'])->name('escrow.hold');
    Route::post('orders/{order}/release', [EscrowController::class, 'releaseFunds'])->name('escrow.release');
    Route::post('orders/{order}/deduct-damage', [EscrowController::class, 'deductForDamage'])->name('escrow.deduct');
    Route::post('orders/{order}/refund', [EscrowController::class, 'refundEscrow'])->name('escrow.refund');
    Route::get('orders/{order}', [EscrowController::class, 'show'])->name('escrow.show');
    Route::get('orders/{order}/all', [EscrowController::class, 'orderEscrows'])->name('escrow.order.all');
    Route::get('history', [EscrowController::class, 'userHistory'])->name('escrow.history');
    Route::get('statistics', [EscrowController::class, 'statistics'])->name('escrow.statistics');
});

// Dispute Routes
Route::middleware('auth:sanctum')->prefix('disputes')->group(function () {
    Route::post('orders/{order}', [DisputeController::class, 'store'])->name('disputes.store');
    Route::get('{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::post('{dispute}/evidence', [DisputeController::class, 'submitEvidence'])->name('disputes.evidence');
    Route::post('{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');
    Route::post('{dispute}/appeal', [DisputeController::class, 'appeal'])->name('disputes.appeal');
    Route::post('{dispute}/close', [DisputeController::class, 'close'])->name('disputes.close');
    Route::get('{dispute}/timeline', [DisputeController::class, 'timeline'])->name('disputes.timeline');
    Route::get('my/list', [DisputeController::class, 'userDisputes'])->name('disputes.user.all');
    Route::get('my/open', [DisputeController::class, 'userOpenDisputes'])->name('disputes.user.open');
    Route::get('my/statistics', [DisputeController::class, 'userStats'])->name('disputes.user.stats');
    Route::get('orders/{order}/statistics', [DisputeController::class, 'orderStats'])->name('disputes.order.stats');
    Route::get('admin/awaiting-resolution', [DisputeController::class, 'awaitingResolution'])->name('disputes.admin.awaiting');
});
