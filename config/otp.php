<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for OTP. You are free to adjust these settings as needed.
    |
    | otp_does_expire | Bool (true or false)
    | Whether the OTP code will expire.
    | If true, the number of seconds specified in otp_timeout_seconds is used to get expiry date
    |
    | otp_timeout_seconds | Numeric
    | Number of seconds after which the OTP expires
    | Taken into consideration only if otp_does_expire is set to true
    |
    | otp_digit_length | Numeric
    | The number of digits that the OTP will have
    |
    | otp_should_encode | Bool (true or false)
    | Whether to hash the OTP before saving in the database
    | Uses framework hashing to hash OTP. See security > hashing in Laravel docs
    |
    */

    'otp_does_expire' => true,
    'otp_timeout_seconds' => 180,
    'otp_digit_length' => 6,
    'otp_should_encode' => false,

    /*
    |--------------------------------------------------------------------------
    | OTP communication services configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for communication channel services providers like Twilio. You are free to adjust these settings as needed.
    | An example service is provided for reference.
    |
    | YOU ARE EXPECTED TO CODE YOUR OWN SERVICE
    |
    | otp_default_communication_service | String
    | The name of the otp service provider you want to use
    |
    | otp_communication_services | Array
    | List of supported communication providers with settings
    | To add a service:
    | 1. Add a new unique key to the services array
    | 2. Specify the class path for your service
    | 3. Add any settings that your particular service uses in key => value pairs
    |
    */
    'otp_default_communication_service' => env('OTP_SERVICE', 'example_service'),
    'otp_communication_services' => [
        'example_service' => [
            'class' => App\Services\ExampleOtpProviderService::class,
            'username' => env('OTP_SERVICE_USERNAME', null),
            'password' => env('OTP_SERVICE_PASSWORD', null),
            'from' => env('OTP_SERVICE_FROM', null)
        ]
    ],
];
