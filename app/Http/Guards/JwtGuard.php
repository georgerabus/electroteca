<?php

namespace App\Http\Guards;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class JwtGuard implements Guard
{
    protected $user;
    protected $provider;
    protected $request;
    protected $jwtService;

    public function __construct(UserProvider $provider, Request $request, JwtService $jwtService)
    {
        $this->provider = $provider;
        $this->request = $request;
        $this->jwtService = $jwtService;
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?User
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if (!$token) {
            return null;
        }

        $user = $this->jwtService->getUserFromToken($token);

        if ($user) {
            $this->user = $user;
        }

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): ?int
    {
        $user = $this->user();
        return $user ? $user->id : null;
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        // This is handled by Fortify, but we need to implement it
        return false;
    }

    /**
     * Set the current user.
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * Get the token from the request (HTTP-Only cookie)
     */
    protected function getTokenFromRequest(): ?string
    {
        // Get token from HTTP-Only cookie
        return $this->request->cookie('access_token');
    }

    /**
     * Set the access token as HTTP-Only cookie
     */
    public function setAccessToken(string $token, int $minutes = 60): void
    {
        // This will be handled by middleware
    }
}

