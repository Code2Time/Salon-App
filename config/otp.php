<?php

return [
    'driver' => env('OTP_DRIVER', 'fixed'),
    'fixed_code' => env('OTP_FIXED_CODE', '2244'),
    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 5),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'template_id' => (int) env('SMSIR_TEMPLATE_ID', 0),
        'parameter_name' => env('SMSIR_PARAMETER_NAME', 'Code'),
    ],
];
