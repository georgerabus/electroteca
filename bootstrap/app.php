<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnforceJwtHttpOnly;
use App\Http\Middleware\ForceHttpsMiddleware;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\JwtCookieMiddleware;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Keep XSRF token unencrypted so the frontend can read it for CSRF headers.
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'XSRF-TOKEN']);
        
        // Trust proxies for HTTPS detection behind load balancers
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
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
        //
    })->create();
