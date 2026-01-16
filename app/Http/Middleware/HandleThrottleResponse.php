<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleThrottleResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
            
            // Check if this is a throttle response (429 status with JSON content)
            if ($response->getStatusCode() === 429 && 
                $response->headers->get('Content-Type') === 'application/json') {
                
                // For login/register requests, return Inertia response instead of JSON
                if ($request->is('login') || $request->is('register')) {
                    $content = json_decode($response->getContent(), true);
                    $message = $content['message'] ?? 'Too many attempts. Please try again later.';
                    
                    return Inertia::render($request->is('register') ? 'auth/register' : 'auth/login', [
                        'errors' => ['email' => $message],
                        'canResetPassword' => true,
                        'status' => null,
                    ])->toResponse($request)->setStatusCode(429);
                }
            }
            
            return $response;
        } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            // If the middleware throws a throttle exception
            if ($request->is('login') || $request->is('register')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                
                return Inertia::render($request->is('register') ? 'auth/register' : 'auth/login', [
                    'errors' => ['email' => 'Too many login attempts. Please try again in ' . $retryAfter . ' seconds.'],
                    'canResetPassword' => true,
                    'status' => null,
                ])->toResponse($request)->setStatusCode(429);
            }
            
            throw $e;
        }
    }
}
