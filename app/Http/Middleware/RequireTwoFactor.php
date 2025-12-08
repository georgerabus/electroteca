<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Make 2FA optional instead of mandatory
        // Users can access all pages, but 2FA is recommended for security
        // To make it mandatory again, uncomment the code below
        
        /*
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Check if 2FA is enabled and confirmed
        $hasTwoFactor = !is_null($user->two_factor_confirmed_at);

        // If 2FA is not enabled, redirect to 2FA setup page
        if (!$hasTwoFactor) {
            // Allow access to 2FA setup and settings pages, logout, and Fortify routes
            $allowedRoutes = [
                'two-factor.show',
                'logout',
                'settings.profile',
                'settings.password',
                'settings.two-factor',
                'settings.appearance',
            ];

            // Allow all Fortify routes (login, register, etc.)
            $path = $request->path();
            $isFortifyRoute = str_starts_with($path, 'login') || 
                             str_starts_with($path, 'register') ||
                             str_starts_with($path, 'logout') ||
                             str_starts_with($path, 'two-factor-challenge') ||
                             str_starts_with($path, 'user/two-factor');

            // Allow all settings routes
            $isSettingsRoute = str_starts_with($path, 'settings');

            $routeName = $request->route()?->getName();
            
            if (!$isFortifyRoute && !$isSettingsRoute && !in_array($routeName, $allowedRoutes)) {
                return redirect()->route('two-factor.show')
                    ->with('error', 'Two-factor authentication is required. Please enable it to continue.');
            }
        }
        */

        return $next($request);
    }
}

