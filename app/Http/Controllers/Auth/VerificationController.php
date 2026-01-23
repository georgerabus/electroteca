<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class VerificationController extends Controller
{
    /**
     * Verify the user's email using a signed URL.
     */
    public function verify(Request $request, $id, $hash)
    {
        // Ensure signature is valid
        if (! $request->hasValidSignature()) {
            return Redirect::route('home')->withErrors(['error' => 'Verification link is invalid or has expired.']);
        }

        $user = User::find($id);

        if (! $user) {
            return Redirect::route('home')->withErrors(['error' => 'User not found.']);
        }

        if (sha1($user->email) !== (string) $hash) {
            return Redirect::route('home')->withErrors(['error' => 'Verification data mismatch.']);
        }

        if ($user->email_verified_at) {
            return Redirect::to(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        Log::info('User email verified', ['user_id' => $user->id]);

        return Redirect::to(route('dashboard', absolute: false).'?verified=1');
    }
}
