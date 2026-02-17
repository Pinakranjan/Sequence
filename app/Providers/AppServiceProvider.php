<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        RateLimiter::for('auth-login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(3)->by($email . '|' . $request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->ip());
        });

        RateLimiter::for('auth-refresh', function (Request $request) {
            $deviceUuid = strtolower((string) $request->input('device_uuid', 'unknown-device'));

            return Limit::perMinute(10)->by($deviceUuid . '|' . $request->ip());
        });

        RateLimiter::for('auth-email-check', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(10)->by($email . '|' . $request->ip());
        });

        // Force HTTPS in production to prevent mixed content warnings
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
