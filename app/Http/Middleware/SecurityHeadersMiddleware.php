<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
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
        $cspNonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $cspNonce);
        Vite::useCspNonce($cspNonce);

        $response = $next($request);

        // Content Security Policy (CSP)
        // Adjust these directives based on your application's needs
        $isProduction = app()->environment('production');

        // Allow explicit overrides via environment variables (comma separated hosts)
        $extraConnect = array_filter(array_map('trim', explode(',', (string) config('security.csp.connect_src', ''))));
        $extraImg = array_filter(array_map('trim', explode(',', (string) config('security.csp.img_src', ''))));
        $allowDev = (bool) config('security.csp.allow_dev', false);
        $allowInlineStyle = (bool) config('security.csp.allow_inline_style', false);

        $scriptSrc = ["'self'", 'https://cdn.paddle.com', "'nonce-{$cspNonce}'"];
        $styleSrc = ["'self'", 'https://fonts.bunny.net', 'https://cdn.paddle.com', "'nonce-{$cspNonce}'"];
        $styleSrcElem = null;
        $styleSrcAttr = null;

        // Base directives
        $csp = [
            "default-src 'self'",
            // By default, do NOT include 'unsafe-inline' or 'unsafe-eval'.
            // Inline scripts/styles should be replaced with external files or nonces/hashes.
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.bunny.net",
            "frame-src https://checkout.paddle.com https://*.paddle.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        // connect-src: allow self plus any explicitly configured backends/CDNs
        $connectSrc = ["'self'", 'https://api.paddle.com', 'https://*.paddle.com'];
        // If in production, allow only https hostnames and configured extras
        if ($isProduction) {
            // Optionally allow https: for remote APIs (if specified via env, list domains explicitly)
            foreach ($extraConnect as $host) {
                $connectSrc[] = $host;
            }
        } else {
            // In non-production, allow local dev tools only when CSP_ALLOW_DEV is true
            if ($allowDev) {
                // Allow local dev hosts on any port
                $connectSrc[] = 'http://localhost:*';
                $connectSrc[] = 'http://127.0.0.1:*';
                $connectSrc[] = 'ws://localhost:*';
                $connectSrc[] = 'ws://127.0.0.1:*';
                // Add any extra hosts configured
                foreach ($extraConnect as $host) {
                    $connectSrc[] = $host;
                }
            } else {
                foreach ($extraConnect as $host) {
                    $connectSrc[] = $host;
                }
            }
        }

        $csp[] = 'connect-src '.implode(' ', $connectSrc);

        // If extra img hosts are provided, append them explicitly (no wildcard https: by default)
        if (count($extraImg) > 0) {
            $csp = array_filter($csp, fn($item) => !str_starts_with($item, 'img-src'));
            $imgParts = array_merge(["'self'", 'data:', 'https:'], $extraImg);
            $csp[] = 'img-src '.implode(' ', $imgParts);
        }

        // Development exceptions for inline/eval (only when explicitly allowed)
        if ($allowDev) {
            $scriptSrc[] = "'unsafe-inline'";
            $scriptSrc[] = "'unsafe-eval'";
            $scriptSrc[] = 'http://localhost:5173';
            $scriptSrc[] = 'http://127.0.0.1:5173';
            $styleSrc[] = "'unsafe-inline'";
        }

        if ($allowInlineStyle) {
            $styleSrcElem = ["'self'", 'https://fonts.bunny.net', 'https://cdn.paddle.com', "'unsafe-inline'"];
            $styleSrcAttr = ["'unsafe-inline'"];
        }

        $csp[] = 'script-src '.implode(' ', $scriptSrc);
        $csp[] = 'style-src '.implode(' ', $styleSrc);
        if ($styleSrcElem) {
            $csp[] = 'style-src-elem '.implode(' ', $styleSrcElem);
        }
        if ($styleSrcAttr) {
            $csp[] = 'style-src-attr '.implode(' ', $styleSrcAttr);
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
