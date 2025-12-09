<?php

namespace aliirfaan\LaravelSimpleOtp;

use aliirfaan\LaravelSimpleOtp\Services\OtpHelperService;
use Illuminate\Support\ServiceProvider;

class SimpleOtpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-simple-otp.php',
            'laravel-simple-otp'
        );

        $this->app->singleton(OtpHelperService::class, function ($app) {
            return new OtpHelperService();
        });
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/laravel-simple-otp.php' => config_path('laravel-simple-otp.php'),
        ], 'laravel-simple-otp-config');
    }
}
