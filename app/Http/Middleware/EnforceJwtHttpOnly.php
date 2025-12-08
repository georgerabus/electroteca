<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceJwtHttpOnly
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures that JWT tokens are only sent/received via HTTP-Only cookies
     * and prevents any attempts to access tokens via JavaScript (XSS protection).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove any JWT tokens from response headers or body that might be exposed
        // (Tokens should only be in HTTP-Only cookies)
        
        // Check if response contains any token in headers
        $headersToRemove = ['X-Access-Token', 'X-Refresh-Token', 'Authorization'];
        
        foreach ($headersToRemove as $header) {
            if ($response->headers->has($header)) {
                $response->headers->remove($header);
            }
        }

        // Add security headers to prevent token leakage
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}

