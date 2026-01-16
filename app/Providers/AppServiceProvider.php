<?php

namespace App\Providers;

use Inertia\Inertia;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //if (app()->environment('local') || app()->environment('production')) {
        //    URL::forceScheme('https');
        //}
        
        // Listen for user registration and send a welcome email.
        Event::listen(Registered::class, function (Registered $event) {
            try {
                Mail::to($event->user->email)->send(new WelcomeMail($event->user));
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email', [
                    'user_id' => $event->user->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        Inertia::share('paddle', [
            'clientToken' => config('services.paddle.client_token'),
            'environment' => config('services.paddle.environment', 'sandbox'),
        ]);
    }
}
