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
    | otp_should_simulate | Bool (true or false)
    | Whether to simulate otp code generation
    |
    | otp_simulate_fillable_digit | Numeric
    | What digits to use to generate OTP if simulation is enabled. The OTP generated will be generated with the fillable digit. Example: 1111
    */

    'otp_does_expire' => env('OTP_DOES_EXPIRE', true),
    'otp_timeout_seconds' => env('OTP_TIMEOUT_SECONDS', 180),
    'otp_digit_length' => env('OTP_DIGIT_LENGTH', 6),
    'otp_should_encode' => env('OTP_SHOULD_ENCODE', true),
    'otp_should_simulate' => env('OTP_SHOULD_SIMULATE', false),
    'otp_simulate_fillable_digit' => env('OTP_SIMULATE_FILLABLE_DIGIT', 1)
];
