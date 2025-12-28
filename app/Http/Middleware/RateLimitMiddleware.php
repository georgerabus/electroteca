<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Auth endpoints: 5 attempts per minute
        if ($request->is('login') || $request->is('register')) {
            if (RateLimiter::tooManyAttempts('auth:' . $request->ip(), 5)) {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again in ' . RateLimiter::availableIn('auth:' . $request->ip()) . ' seconds.',
                ], 429);
            }
            RateLimiter::hit('auth:' . $request->ip(), 60);
        }

        // Two-factor verification: 10 attempts per minute
        if ($request->is('*/two-factor-challenge') || $request->is('*/two-factor-email')) {
            if (RateLimiter::tooManyAttempts('2fa:' . $request->ip(), 10)) {
                return response()->json([
                    'message' => 'Too many 2FA attempts. Please try again later.',
                ], 429);
            }
            RateLimiter::hit('2fa:' . $request->ip(), 60);
        }

        // Checkout: 3 attempts per minute
        if ($request->is('checkout') && $request->isMethod('post')) {
            if (RateLimiter::tooManyAttempts('checkout:' . auth()->id(), 3)) {
                return response()->json([
                    'message' => 'Too many checkout attempts. Please try again in a moment.',
                ], 429);
            }
            RateLimiter::hit('checkout:' . auth()->id(), 60);
        }

        // Borrow/Loan requests: 5 per minute per user
        if ($request->is('products/*/borrow') || $request->is('loans/*/request-return')) {
            if (auth()->check()) {
                if (RateLimiter::tooManyAttempts('loans:' . auth()->id(), 5)) {
                    return response()->json([
                        'message' => 'Too many loan requests. Please try again later.',
                    ], 429);
                }
                RateLimiter::hit('loans:' . auth()->id(), 60);
            }
        }

        return $next($request);
    }
}
