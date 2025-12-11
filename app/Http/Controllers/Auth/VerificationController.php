<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            return Redirect::route('main')->withErrors(['error' => 'Verification link is invalid or has expired.']);
        }

        $user = User::find($id);

        if (! $user) {
            return Redirect::route('main')->withErrors(['error' => 'User not found.']);
        }

        if (sha1($user->email) !== (string) $hash) {
            return Redirect::route('main')->withErrors(['error' => 'Verification data mismatch.']);
        }

        if ($user->email_verified_at) {
            return Redirect::route('main')->with('success', 'Email already verified.');
        }

        $user->email_verified_at = now();
        $user->save();

        Log::info('User email verified', ['user_id' => $user->id]);

        return Redirect::route('main')->with('success', 'Your email has been verified. You can now request loans.');
    }
}
