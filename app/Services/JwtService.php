<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class JwtService
{
    /**
     * Algorithm for JWT signing (using RS256 for better security)
     */
    private string $algorithm = 'HS256'; // Can be changed to RS256 for asymmetric keys

    /**
     * Token expiration time in minutes
     */
    private int $expirationMinutes = 60;

    /**
     * Refresh token expiration time in days
     */
    private int $refreshExpirationDays = 30;

    /**
     * Get the JWT secret key
     */
    private function getSecretKey(): string
    {
        return config('app.key'); // Using Laravel's app key as JWT secret
    }

    /**
     * Generate a JWT access token for a user
     */
    public function generateAccessToken(User $user): string
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addMinutes($this->expirationMinutes);

        $payload = [
            'iss' => config('app.url'), // Issuer
            'aud' => config('app.url'), // Audience
            'iat' => $now->timestamp, // Issued at
            'exp' => $expiresAt->timestamp, // Expiration
            'sub' => $user->id, // Subject (user ID)
            'jti' => uniqid('', true), // JWT ID (unique token identifier)
            'type' => 'access',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ];

        return JWT::encode($payload, $this->getSecretKey(), $this->algorithm);
    }

    /**
     * Generate a JWT refresh token for a user
     */
    public function generateRefreshToken(User $user): string
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addDays($this->refreshExpirationDays);

        $tokenId = uniqid('refresh_', true);
        
        // Store refresh token in cache for revocation checking
        Cache::put("refresh_token:{$user->id}:{$tokenId}", true, $expiresAt);

        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => $now->timestamp,
            'exp' => $expiresAt->timestamp,
            'sub' => $user->id,
            'jti' => $tokenId,
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $this->getSecretKey(), $this->algorithm);
    }

    /**
     * Verify and decode a JWT token
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->getSecretKey(), $this->algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verify refresh token and check if it's revoked
     */
    public function verifyRefreshToken(string $token): ?array
    {
        $decoded = $this->verifyToken($token);

        if (!$decoded || ($decoded['type'] ?? null) !== 'refresh') {
            return null;
        }

        // Check if token is revoked
        $tokenId = $decoded['jti'] ?? null;
        $userId = $decoded['sub'] ?? null;

        if ($tokenId && $userId) {
            $isRevoked = !Cache::has("refresh_token:{$userId}:{$tokenId}");
            if ($isRevoked) {
                return null;
            }
        }

        return $decoded;
    }

    /**
     * Revoke a refresh token
     */
    public function revokeRefreshToken(string $token): bool
    {
        $decoded = $this->verifyToken($token);

        if (!$decoded || ($decoded['type'] ?? null) !== 'refresh') {
            return false;
        }

        $tokenId = $decoded['jti'] ?? null;
        $userId = $decoded['sub'] ?? null;

        if ($tokenId && $userId) {
            Cache::forget("refresh_token:{$userId}:{$tokenId}");
            return true;
        }

        return false;
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public function revokeAllRefreshTokens(User $user): void
    {
        // This would require tracking all tokens, for now we'll use a pattern
        // In production, you might want to store tokens in database
        Cache::tags(["user_refresh_tokens:{$user->id}"])->flush();
    }

    /**
     * Get user from access token
     */
    public function getUserFromToken(string $token): ?User
    {
        $decoded = $this->verifyToken($token);

        if (!$decoded || ($decoded['type'] ?? null) !== 'access') {
            return null;
        }

        $userId = $decoded['sub'] ?? null;

        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }
}

