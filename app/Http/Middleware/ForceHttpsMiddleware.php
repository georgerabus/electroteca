<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enforce HTTPS in production or when explicitly enabled
        $forceHttps = config('app.force_https', false);
        
        // Skip HTTPS enforcement in local development unless explicitly enabled
        if (app()->environment('local') && !$forceHttps) {
            return $next($request);
        }

        // Redirect HTTP to HTTPS
        if (!$request->secure() && $forceHttps) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}

