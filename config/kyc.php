<?php

return [
    'driver' => env('KYC_DRIVER', 'http'),
    'default_provider' => env('KYC_PROVIDER_NAME', 'configured-http'),
    'base_url' => env('KYC_BASE_URL', ''),
    'connect_timeout' => (int) env('KYC_CONNECT_TIMEOUT', 10),
    'timeout' => (int) env('KYC_TIMEOUT', 30),
    'maximum_attempts' => (int) env('KYC_MAXIMUM_ATTEMPTS', 8),
    'retry_delay_minutes' => (int) env('KYC_RETRY_DELAY_MINUTES', 10),
    'duplicate_scope' => env('KYC_DUPLICATE_SCOPE', 'platform'),

    'auth' => [
        'scheme' => env('KYC_AUTH_SCHEME', 'bearer'),
        'key' => env('KYC_API_KEY', ''),
        'secret' => env('KYC_API_SECRET', ''),
        'header' => env('KYC_API_KEY_HEADER', 'X-API-Key'),
    ],

    'endpoints' => [
        'nin' => env('KYC_NIN_ENDPOINT', '/verify/nin'),
        'bvn' => env('KYC_BVN_ENDPOINT', '/verify/bvn'),
    ],

    'success_statuses' => ['verified', 'success', 'successful', 'approved'],
    'pending_statuses' => ['pending', 'processing', 'queued', 'in_progress'],

    'response' => [
        'status' => env('KYC_RESPONSE_STATUS_PATH', 'status'),
        'message' => env('KYC_RESPONSE_MESSAGE_PATH', 'message'),
        'name' => env('KYC_RESPONSE_NAME_PATH', 'data.name'),
        'dob' => env('KYC_RESPONSE_DOB_PATH', 'data.date_of_birth'),
        'reference' => env('KYC_RESPONSE_REFERENCE_PATH', 'data.reference'),
    ],
];
