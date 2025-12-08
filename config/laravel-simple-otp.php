<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for OTP. You are free to adjust these settings as needed.
    |
    | otp_type | String
    | The type of OTP to generate. Can be 'numeric' or 'alphanumeric'
    | Default is 'numeric'
    |
    | otp_timeout_seconds | Numeric
    | Number of seconds after which the OTP expires
    |
    | otp_length | Numeric
    | The number of digits that the OTP will have
    | 
    | otp_should_simulate | Bool (true or false)
    | Whether to simulate otp code generation
    |
    | otp_simulated_code | String
    | The OTP code to use if simulation is enabled. The OTP generated will be generated with the simulated OTP code. Example: 256354
    */

    'otp_type' => env('OTP_TYPE', 'numeric'),
    'otp_timeout_seconds' => env('OTP_TIMEOUT_SECONDS', 180),
    'otp_length' => env('OTP_LENGTH', 6),
    'otp_should_simulate' => env('OTP_SHOULD_SIMULATE', false),
    'otp_simulated_code' => env('OTP_SIMULATED_CODE'),
];
