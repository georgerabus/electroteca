<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\EscrowController;
use App\Http\Controllers\DisputeController;

Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');

Route::post('webhooks/paddle', [PaymentController::class, 'paddleWebhook'])->name('api.webhooks.paddle');

// Escrow Routes
Route::middleware('auth:sanctum')->prefix('escrow')->name('api.escrow.')->group(function () {
    Route::post('orders/{order}/hold', [EscrowController::class, 'holdFunds'])->name('hold');
    Route::post('orders/{order}/release', [EscrowController::class, 'releaseFunds'])->name('release');
    Route::post('orders/{order}/deduct-damage', [EscrowController::class, 'deductForDamage'])->name('deduct');
    Route::post('orders/{order}/refund', [EscrowController::class, 'refundEscrow'])->name('refund');
    Route::get('orders/{order}', [EscrowController::class, 'show'])->name('show');
    Route::get('orders/{order}/all', [EscrowController::class, 'orderEscrows'])->name('order.all');
    Route::get('history', [EscrowController::class, 'userHistory'])->name('history');
    Route::get('statistics', [EscrowController::class, 'statistics'])->name('statistics');
});

// Dispute Routes
Route::middleware('auth:sanctum')->prefix('disputes')->name('api.disputes.')->group(function () {
    Route::post('orders/{order}', [DisputeController::class, 'store'])->name('store');
    Route::get('{dispute}', [DisputeController::class, 'show'])->name('show');
    Route::post('{dispute}/evidence', [DisputeController::class, 'submitEvidence'])->name('evidence');
    Route::post('{dispute}/resolve', [DisputeController::class, 'resolve'])->name('resolve');
    Route::post('{dispute}/appeal', [DisputeController::class, 'appeal'])->name('appeal');
    Route::post('{dispute}/close', [DisputeController::class, 'close'])->name('close');
    Route::get('{dispute}/timeline', [DisputeController::class, 'timeline'])->name('timeline');
    Route::get('my/list', [DisputeController::class, 'userDisputes'])->name('user.all');
    Route::get('my/open', [DisputeController::class, 'userOpenDisputes'])->name('user.open');
    Route::get('my/statistics', [DisputeController::class, 'userStats'])->name('user.stats');
    Route::get('orders/{order}/statistics', [DisputeController::class, 'orderStats'])->name('order.stats');
    Route::get('admin/awaiting-resolution', [DisputeController::class, 'awaitingResolution'])->name('admin.awaiting');
});
