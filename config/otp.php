<?php

return [
    'driver' => env('OTP_DRIVER', 'fixed'),
    'fixed_code' => env('OTP_FIXED_CODE', '1111'),
    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 5),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
];
