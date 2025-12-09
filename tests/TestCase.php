<?php

namespace aliirfaan\LaravelSimpleOtp\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use aliirfaan\LaravelSimpleOtp\SimpleOtpServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            SimpleOtpServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set default OTP config
        $app['config']->set('laravel-simple-otp.otp_type', 'numeric');
        $app['config']->set('laravel-simple-otp.otp_timeout_seconds', 180);
        $app['config']->set('laravel-simple-otp.otp_length', 6);
        $app['config']->set('laravel-simple-otp.otp_should_simulate', false);
        $app['config']->set('laravel-simple-otp.otp_simulated_code', '123456');
    }
}

