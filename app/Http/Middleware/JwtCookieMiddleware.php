<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtCookieMiddleware
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't authenticate via JWT if user is logging out
        $isLogoutRequest = $request->routeIs('logout') || $request->is('logout');
        
        // Try to authenticate using JWT token from cookie (only if not already authenticated and not logging out)
        if (!Auth::check() && !$isLogoutRequest) {
            $accessToken = $request->cookie('access_token');
            
            if ($accessToken) {
                $user = $this->jwtService->getUserFromToken($accessToken);
                
                if ($user) {
                    Auth::login($user);
                } else {
                    // Token is invalid, try refresh token
                    $refreshToken = $request->cookie('refresh_token');
                    
                    if ($refreshToken) {
                        $decoded = $this->jwtService->verifyRefreshToken($refreshToken);
                        
                        if ($decoded) {
                            $user = \App\Models\User::find($decoded['sub']);
                            
                            if ($user) {
                                Auth::login($user);
                            }
                        }
                    }
                }
            }
        }

        $response = $next($request);

        // If user is logging out, clear JWT cookies
        if ($isLogoutRequest || !Auth::check()) {
            // Clear JWT cookies by setting them to expire immediately
            $response->cookie('access_token', '', -1, '/', null, true, true, false, 'Strict');
            $response->cookie('refresh_token', '', -1, '/', null, true, true, false, 'Strict');
        } elseif (Auth::check() && !$request->cookie('access_token')) {
            // If user is authenticated via session, set JWT token as HTTP-Only cookie
            $user = Auth::user();
            $accessToken = $this->jwtService->generateAccessToken($user);
            $refreshToken = $this->jwtService->generateRefreshToken($user);

            // Set access token as HTTP-Only cookie (60 minutes)
            $response->cookie('access_token', $accessToken, 60, '/', null, true, true, false, 'Strict');
            
            // Set refresh token as HTTP-Only cookie (30 days)
            $response->cookie('refresh_token', $refreshToken, 60 * 24 * 30, '/', null, true, true, false, 'Strict');
        }

        return $response;
    }
}
