<?php

use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->name('register.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::post('forgot-password/email-otp', [PasswordResetOtpController::class, 'send'])
        ->middleware('throttle:login')
        ->name('password.email-otp');

    Route::post('reset-password/email-otp', [PasswordResetOtpController::class, 'reset'])
        ->middleware('throttle:login')
        ->name('password.reset-otp');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Social authentication routes
    Route::get('auth/{provider}', [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])
        ->name('social.redirect');

    Route::get('auth/{provider}/callback', [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])
        ->name('social.callback');

    // Custom two-factor challenge routes (supports email OTP)
    // These override Fortify's default 2FA routes to add email OTP support
    Route::get('two-factor-challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'create'])
        ->name('two-factor.login');

    Route::post('two-factor-challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:two-factor')
        ->name('two-factor.login.store');
});
