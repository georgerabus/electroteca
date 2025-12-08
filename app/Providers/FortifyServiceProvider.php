<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Services\EmailTwoFactorService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom logout response to clear JWT cookies
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureEmailTwoFactor();
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        // twoFactorChallengeView is configured in configureEmailTwoFactor()

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Configure email-based two-factor authentication.
     */
    private function configureEmailTwoFactor(): void
    {
        // Custom 2FA challenge view - handled by TwoFactorChallengeController
        // This sends email OTP when the challenge page is accessed
        Fortify::twoFactorChallengeView(function (Request $request) {
            $loginId = $request->session()->get('login.id');
            
            if (!$loginId) {
                return redirect()->route('login');
            }

            $user = \App\Models\User::find($loginId);
            
            if (!$user || !$user->hasEnabledTwoFactorAuthentication()) {
                return redirect()->route('login');
            }

            // Send email OTP
            $emailTwoFactorService = app(EmailTwoFactorService::class);
            $emailTwoFactorService->sendOtp($user);

            return Inertia::render('auth/two-factor-challenge', [
                'emailOtpSent' => true,
            ]);
        });
    }
}
