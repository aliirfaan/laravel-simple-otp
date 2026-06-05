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

        $app['config']->set('laravel-simple-otp.default_profile', 'default');
        $app['config']->set('laravel-simple-otp.otp_profiles', [
            'default' => [
                'otp_type' => 'numeric',
                'otp_length' => 6,
                'otp_timeout_seconds' => 180,
                'otp_should_simulate' => false,
                'otp_simulated_code' => '123456',
                'otp_retention_days' => 0,
            ],
            'length_8' => [
                'otp_type' => 'numeric',
                'otp_length' => 8,
                'otp_timeout_seconds' => 180,
            ],
            'length_4' => [
                'otp_type' => 'numeric',
                'otp_length' => 4,
                'otp_timeout_seconds' => 180,
            ],
            'length_12' => [
                'otp_type' => 'numeric',
                'otp_length' => 12,
                'otp_timeout_seconds' => 180,
            ],
            'length_3' => [
                'otp_type' => 'numeric',
                'otp_length' => 3,
                'otp_timeout_seconds' => 180,
            ],
            'length_13' => [
                'otp_type' => 'numeric',
                'otp_length' => 13,
                'otp_timeout_seconds' => 180,
            ],
            'alphanumeric_8' => [
                'otp_type' => 'alphanumeric',
                'otp_length' => 8,
                'otp_timeout_seconds' => 180,
            ],
            'password_reset' => [
                'otp_type' => 'alphanumeric',
                'otp_length' => 8,
                'otp_timeout_seconds' => 600,
            ],
        ]);
    }

    protected function setDefaultProfileOverrides(array $overrides): void
    {
        $profiles = config('laravel-simple-otp.otp_profiles');
        $profiles['default'] = array_merge($profiles['default'], $overrides);
        config(['laravel-simple-otp.otp_profiles' => $profiles]);
    }
}
