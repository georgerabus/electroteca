<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to provider.
     */
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle provider callback.
     */
    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['error' => 'Failed to authenticate with ' . ucfirst($provider)]);
        }

        $user = $this->findOrCreateUser($provider, $socialUser);

        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }

    /**
     * Find or create user from social account.
     */
    protected function findOrCreateUser(string $provider, $socialUser)
    {
        // Check if social account exists
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            return $socialAccount->user;
        }

        // Check if user with this email exists
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail(),
                'password' => bcrypt(Str::random(32)), // Random password since they use social login
                'email_verified_at' => now(), // Social providers verify emails
            ]);
        }

        // Create social account
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'email' => $socialUser->getEmail(),
            'name' => $socialUser->getName(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        return $user;
    }

    /**
     * Validate provider.
     */
    protected function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook', 'apple', 'microsoft'])) {
            abort(404);
        }
    }
}
