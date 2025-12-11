<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailTwoFactorService
{
    /**
     * Generate and send email OTP code to user.
     */
    public function sendOtp(User $user): bool
    {
        // Generate 6-digit OTP code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Set expiration time (10 minutes)
        $expiresAt = Carbon::now()->addMinutes(10);

        // Store code and expiration in user record
        $user->email_2fa_code = bcrypt($code);
        $user->email_2fa_expires_at = $expiresAt;
        $user->save();

        try {
            // Send email with OTP code
            Mail::send('emails.two-factor-otp', [
                'code' => $code,
                'user' => $user,
                'expiresAt' => $expiresAt,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Your Two-Factor Authentication Code');
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA email OTP', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify email OTP code.
     */
    public function verifyOtp(User $user, string $code, bool $markVerified = true): bool
    {
        // Check if code exists and hasn't expired
        if (!$user->email_2fa_code || !$user->email_2fa_expires_at) {
            return false;
        }

        // Check if code has expired
        if (Carbon::now()->isAfter($user->email_2fa_expires_at)) {
            // Clear expired code
            $user->email_2fa_code = null;
            $user->email_2fa_expires_at = null;
            $user->save();

            return false;
        }

        // Verify the code
        if (!password_verify($code, $user->email_2fa_code)) {
            return false;
        }

        // Code is valid - optionally mark as verified and clear the code
        if ($markVerified) {
            $user->email_2fa_verified_at = Carbon::now();
        }
        $user->email_2fa_code = null;
        $user->email_2fa_expires_at = null;
        $user->save();

        return true;
    }

    /**
     * Clear email 2FA code (e.g., after successful verification or expiration).
     */
    public function clearOtp(User $user): void
    {
        $user->email_2fa_code = null;
        $user->email_2fa_expires_at = null;
        $user->save();
    }
}
