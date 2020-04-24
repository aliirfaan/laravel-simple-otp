<?php

namespace aliirfaan\LaravelSimpleOtp;

use aliirfaan\LaravelSimpleOtp\Services\OtpHelperService;

class SimpleOtpServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('aliirfaan\LaravelSimpleOtp\Services\OtpHelperService', function ($app) {
            return new OtpHelperService();
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/otp.php' => config_path('otp.php'),
        ]);
    }
}
