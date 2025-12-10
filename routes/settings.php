<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
    
    // Email-based two-factor routes
    Route::post('settings/two-factor/email/send', [TwoFactorAuthenticationController::class, 'sendEmailOtp'])
        ->name('two-factor.email.send');

    Route::post('settings/two-factor/email/verify', [TwoFactorAuthenticationController::class, 'verifyEmailOtp'])
        ->name('two-factor.email.verify');

    Route::delete('settings/two-factor/email', [TwoFactorAuthenticationController::class, 'disableEmailOtp'])
        ->name('two-factor.email.disable');
});
