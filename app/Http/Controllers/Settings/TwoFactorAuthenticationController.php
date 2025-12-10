<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Services\EmailTwoFactorService;
use Laravel\Fortify\Features;

class TwoFactorAuthenticationController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [new Middleware('password.confirm', only: ['show'])]
            : [];
    }

    /**
     * Show the user's two-factor authentication settings page.
     */
    public function show(TwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();

        return Inertia::render('settings/two-factor', [
            // TOTP (authenticator app) enabled
            'twoFactorEnabled' => $request->user()->hasTOTPEnabled(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            // Email-based 2FA enabled
            'emailTwoFactorEnabled' => $request->user()->hasEmailTwoFactorEnabled(),
            // Overall flag for convenience
            'anyTwoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
        ]);
    }

    /**
     * Send an email OTP to enable email-based two-factor authentication.
     */
    public function sendEmailOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        $service = app(EmailTwoFactorService::class);
        $sent = $service->sendOtp($user);

        if ($sent) {
            return back()->with('status', 'Email OTP sent. Check your inbox.');
        }

        return back()->with('error', 'Failed to send email OTP.');
    }

    /**
     * Verify the provided email OTP and enable email 2FA for the user.
     */
    public function verifyEmailOtp(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();

        $service = app(EmailTwoFactorService::class);

        if ($service->verifyOtp($user, $request->input('code'))) {
            return back()->with('status', 'Email two-factor authentication enabled.');
        }

        return back()->withErrors(['code' => 'The provided code is invalid or expired.']);
    }

    /**
     * Disable email-based two-factor authentication for the user.
     */
    public function disableEmailOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->email_2fa_code = null;
        $user->email_2fa_expires_at = null;
        $user->email_2fa_verified_at = null;
        $user->save();

        return back()->with('status', 'Email two-factor authentication disabled.');
    }
}
