<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP configuration
    |--------------------------------------------------------------------------
    |
    | default_profile | String
    | The profile name used when no profile is passed to generateOtpCode() or persistOtpCode()
    |
    | otp_profiles | Array
    | Named profiles. Each profile supports:
    | - otp_type: 'numeric' or 'alphanumeric'
    | - otp_length: code length
    | - otp_timeout_seconds: expiry TTL when persisting
    | - otp_should_simulate: simulate OTP generation (testing)
    | - otp_simulated_code: code used when simulation is enabled
    | - otp_retention_days: days to retain records before pruning (0 = no pruning)
    |
    | When a profile omits a key, the default_profile values are used as fallback.
    */

    'default_profile' => env('OTP_DEFAULT_PROFILE', 'default'),

    'otp_profiles' => [
        'default' => [
            'otp_type' => env('OTP_TYPE', 'numeric'),
            'otp_length' => (int) env('OTP_LENGTH', 6),
            'otp_timeout_seconds' => (int) env('OTP_TIMEOUT_SECONDS', 180),
            'otp_should_simulate' => env('OTP_SHOULD_SIMULATE', false),
            'otp_simulated_code' => env('OTP_SIMULATED_CODE'),
            'otp_retention_days' => (int) env('OTP_RETENTION_DAYS', 0),
        ],
    ],
];
