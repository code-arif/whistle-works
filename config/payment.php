<?php

return [
    'success_url' => env('PAYMENT_SUCCESS_URL', env('APP_URL') . '/api/v1/referee/payment/success'),
    'cancel_url' => env('PAYMENT_CANCEL_URL', env('APP_URL') . '/api/v1/referee/payment/cancel'),
    'retry_cooldown' => env('PAYMENT_RETRY_COOLDOWN', 5), // minutes
    'session_expiry' => env('PAYMENT_SESSION_EXPIRY', 30), // minutes
];
