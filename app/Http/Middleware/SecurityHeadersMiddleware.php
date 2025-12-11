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
        // Generate a CSP nonce so inline snippets can stay locked down without unsafe-inline
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        // Content Security Policy (CSP)
        // Adjust these directives based on your application's needs
        $isProduction = app()->environment('production');

        // Allow explicit overrides via environment variables (comma separated hosts)
        $extraConnect = array_filter(array_map('trim', explode(',', env('CSP_CONNECT_SRC', ''))));
        $extraImg = array_filter(array_map('trim', explode(',', env('CSP_IMG_SRC', ''))));
        $allowDev = filter_var(env('CSP_ALLOW_DEV', app()->environment('local') ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);

        // Base directives
        $scriptSrc = ["'self'", "'nonce-{$nonce}'"];
        $styleSrc = ["'self'", "'nonce-{$nonce}'", 'https://fonts.bunny.net'];
        $imgSrc = ["'self'", 'data:'];
        $fontSrc = ["'self'", 'data:', 'https://fonts.bunny.net'];
        $csp = [
            "default-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        [$viteHttpHosts, $viteSocketHosts] = $this->viteDevServerSources();

        // connect-src: allow self plus any explicitly configured backends/CDNs
        $connectSrc = array_merge(["'self'"], $viteHttpHosts, $viteSocketHosts);

        if (! empty($viteHttpHosts)) {
            $scriptSrc = array_merge($scriptSrc, $viteHttpHosts);
            $styleSrc = array_merge($styleSrc, $viteHttpHosts);
        }

        // If in production, allow only https hostnames and configured extras
        if ($isProduction) {
            // Optionally allow https: for remote APIs (if specified via env, list domains explicitly)
            foreach ($extraConnect as $host) {
                $connectSrc[] = $host;
            }
        } else {
            // In non-production, allow local dev tools only when CSP_ALLOW_DEV is true
            if ($allowDev) {
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

        if (count($extraImg) > 0) {
            $imgSrc = array_merge($imgSrc, $extraImg);
        }

        // Development exceptions for inline/eval (only when explicitly allowed)
        if ($allowDev) {
            // Append safe dev-only allowances for script/style necessary for local tooling
            $scriptSrc = array_merge($scriptSrc, ["'unsafe-inline'", "'unsafe-eval'"]);
            $styleSrc[] = "'unsafe-inline'";
        }

        // Remote font providers require style+font allowance and preconnect
        $connectSrc[] = 'https://fonts.bunny.net';

        $csp[] = 'connect-src '.implode(' ', array_unique($connectSrc));

        $csp[] = 'script-src '.implode(' ', array_unique($scriptSrc));
        $csp[] = 'style-src '.implode(' ', array_unique($styleSrc));
        $csp[] = 'img-src '.implode(' ', array_unique($imgSrc));
        $csp[] = 'font-src '.implode(' ', array_unique($fontSrc));

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

    /**
     * Detect the currently configured Vite dev server hosts (if any).
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    protected function viteDevServerSources(): array
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return [[], []];
        }

        $url = trim(file_get_contents($hotFile));

        if ($url === '') {
            return [[], []];
        }

        $httpHosts = [$url];
        $socketHosts = [];
        $parts = parse_url($url);

        if ($parts !== false && isset($parts['scheme'], $parts['host'])) {
            $socketScheme = $parts['scheme'] === 'https' ? 'wss' : 'ws';
            $host = $parts['host'];
            $port = isset($parts['port']) ? ':'.$parts['port'] : '';
            $socketHosts[] = "{$socketScheme}://{$host}{$port}";
        }

        return [$httpHosts, $socketHosts];
    }
}
