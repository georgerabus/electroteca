<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailTwoFactorService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class PasswordResetOtpController extends Controller
{
    /**
     * Send a 6-digit code to the user's email for password reset.
     */
    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Avoid account enumeration by returning a generic response
        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            $service = app(EmailTwoFactorService::class);
            $service->sendOtp($user);
        }

        return back()->with('status', 'If an account exists for that email, we sent a 6-digit code.');
    }

    /**
     * Verify the email code and reset the password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return back()->withErrors([
                'code' => 'The provided code is invalid or expired.',
            ]);
        }

        $service = app(EmailTwoFactorService::class);
        if (! $service->verifyOtp($user, $validated['code'], false)) {
            return back()->withErrors([
                'code' => 'The provided code is invalid or expired.',
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Password reset successfully. You can log in with your new password.');
    }
}
