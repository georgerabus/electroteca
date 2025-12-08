<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy (CSP)
        // Adjust these directives based on your application's needs
        $isProduction = app()->environment('production');
        
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // 'unsafe-inline' and 'unsafe-eval' needed for Vite/React in dev
            "style-src 'self' 'unsafe-inline'", // 'unsafe-inline' needed for inline styles
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        // Configure connect-src based on environment
        if ($isProduction) {
            $csp[] = "connect-src 'self' https:";
            $csp[] = "upgrade-insecure-requests";
            // In production, you may want stricter CSP
            // Remove 'unsafe-inline' and 'unsafe-eval' if possible
            // You'll need to use nonces or hashes for inline scripts/styles
        } else {
            // In development, allow Vite dev server connections (WebSocket for HMR)
            // Remove the default connect-src and add a more permissive one
            $csp = array_filter($csp, fn($item) => !str_starts_with($item, 'connect-src'));
            $csp[] = "connect-src 'self' https: ws: wss: http://localhost:* http://127.0.0.1:*";
            // Also allow Vite's script sources - Vite runs on localhost:5173
            $csp = array_filter($csp, fn($item) => !str_starts_with($item, 'script-src'));
            $csp[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173";
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // X-Frame-Options: Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // X-Content-Type-Options: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection: Enable XSS filtering (legacy, but still useful)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy: Control browser features
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), speaker=()'
        );

        // Strict-Transport-Security (HSTS): Force HTTPS
        // Only set in production or when HTTPS is enabled
        if (config('app.force_https', false) || $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}

