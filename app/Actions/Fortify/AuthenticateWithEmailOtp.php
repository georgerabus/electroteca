<?php

namespace App\Actions\Fortify;

use App\Services\EmailTwoFactorService;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class AuthenticateWithEmailOtp
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        // If recovery code is provided, use Fortify's default recovery code handling
        if ($recoveryCode) {
            return $this->verifyRecoveryCode($user, $recoveryCode);
        }

        // Try email OTP first
        $emailTwoFactorService = app(EmailTwoFactorService::class);
        if ($code && $emailTwoFactorService->verifyOtp($user, $code)) {
            return true;
        }

        // Fall back to TOTP if email OTP fails
        if ($code && $user->hasTOTPEnabled()) {
            $provider = app(TwoFactorAuthenticationProvider::class);
            try {
                $secret = decrypt($user->two_factor_secret);
            } catch (DecryptException $e) {
                Log::warning('Failed to decrypt two_factor_secret for user id '.optional($user)->id.'. '.$e->getMessage());
                return false;
            }

            return $provider->verify(
                $secret,
                $code
            );
        }

        return false;
    }

    /**
     * Verify recovery code.
     */
    protected function verifyRecoveryCode($user, string $recoveryCode): bool
    {
        if (!in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user))) {
            return false;
        }

        return $user->recoveryCodes()->contains(function ($code) use ($recoveryCode) {
            return hash_equals($code, $recoveryCode) ? $user->recoveryCodes()->remove($code) : false;
        });
    }
}

