<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailTwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorChallengeController extends Controller
{
    /**
     * Show the two-factor authentication challenge view.
     */
    public function create(Request $request)
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($request->session()->get('login.id'));

        if (!$user || (!$user->hasEmailTwoFactorEnabled() && !$user->hasTOTPEnabled())) {
            return redirect()->route('login');
        }

        // If user has email 2FA enabled, send email OTP. Otherwise render challenge (TOTP expected).
        $emailTwoFactorService = app(EmailTwoFactorService::class);
        if ($user->hasEmailTwoFactorEnabled()) {
            $emailTwoFactorService->sendOtp($user);
            return inertia('auth/two-factor-challenge', [
                'emailOtpSent' => true,
            ]);
        }

        // Render the challenge view (TOTP expected)
        return inertia('auth/two-factor-challenge', [
            'emailOtpSent' => false,
        ]);
    }

    /**
     * Handle the two-factor authentication challenge.
     */
    public function store(Request $request)
    {
        $user = \App\Models\User::find($request->session()->get('login.id'));

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        // Handle recovery code
        if ($recoveryCode) {
            if (!in_array(\Laravel\Fortify\TwoFactorAuthenticatable::class, class_uses_recursive($user))) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['Recovery codes are not enabled for this account.'],
                ]);
            }

            try {
                $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (DecryptException $e) {
                Log::warning('Failed to decrypt two_factor_recovery_codes for user id '.$user->id.'. '.$e->getMessage());
                throw ValidationException::withMessages([
                    'recovery_code' => ['Two-factor recovery codes are invalid or corrupted. Please disable and re-enable two-factor authentication.'],
                ]);
            }

            if (!in_array($recoveryCode, $recoveryCodes)) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['The provided recovery code is invalid.'],
                ]);
            }

            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$recoveryCode]));
            $user->forceFill([
                'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            ])->save();

            return $this->authenticate($request, $user);
        }

        // Try email OTP first
        if ($code) {
            $emailTwoFactorService = app(EmailTwoFactorService::class);
            if ($emailTwoFactorService->verifyOtp($user, $code)) {
                return $this->authenticate($request, $user);
            }

            // Fall back to TOTP if email OTP fails
            if ($user->hasEnabledTwoFactorAuthentication()) {
                $provider = app(TwoFactorAuthenticationProvider::class);
                try {
                    $secret = decrypt($user->two_factor_secret);
                } catch (DecryptException $e) {
                    Log::warning('Failed to decrypt two_factor_secret for user id '.$user->id.'. '.$e->getMessage());
                    throw ValidationException::withMessages([
                        'code' => ['Two-factor secret is invalid or corrupted. Please disable and re-enable two-factor authentication.'],
                    ]);
                }

                if ($provider->verify($secret, $code)) {
                    return $this->authenticate($request, $user);
                }
            }
        }

        throw ValidationException::withMessages([
            'code' => ['The provided two factor authentication code was invalid.'],
        ]);
    }

    /**
     * Authenticate the user and redirect.
     */
    protected function authenticate(Request $request, $user)
    {
        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();
        $request->session()->forget('login.id');

        return redirect()->intended(config('fortify.home'));
    }
}

