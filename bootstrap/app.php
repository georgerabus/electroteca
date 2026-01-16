<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnforceJwtHttpOnly;
use App\Http\Middleware\ForceHttpsMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleThrottleResponse;
use App\Http\Middleware\JwtCookieMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Keep preference cookies unencrypted; leave XSRF-TOKEN encrypted so Laravel can decrypt the header axios/Inertia sends.
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        
        // Trust proxies for HTTPS detection behind load balancers
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleThrottleResponse::class,
            RateLimitMiddleware::class,
            ForceHttpsMiddleware::class,
            SecurityHeadersMiddleware::class,
            JwtCookieMiddleware::class,
            EnforceJwtHttpOnly::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Require 2FA for authenticated users (applied to auth routes)
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'require.2fa' => RequireTwoFactor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            // Handle rate limit exceptions for login/register
            if ($request->is('login') || $request->is('register')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                
                return Inertia::render($request->is('register') ? 'auth/register' : 'auth/login', [
                    'errors' => ['email' => 'Too many login attempts. Please try again in ' . $retryAfter . ' seconds.'],
                    'canResetPassword' => true,
                    'status' => null,
                ])->toResponse($request)->setStatusCode(429);
            }
        });
    })->create();
