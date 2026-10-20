<?php

$origins = array_values(array_filter(array_map(
    static fn (string $origin): string => rtrim(trim($origin), '/'),
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'https://mgs.com.ng')))
)));

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-API-Key',
        'X-MGS-Public-Key',
        'X-MGS-Signature',
        'X-MGS-Timestamp',
        'X-MGS-Nonce',
        'X-MGS-2FA-Code',
        'X-Idempotency-Key',
        'X-Requested-With',
        'X-Business-ID',
        'X-Device-ID',
        'X-Browser-Auth',
        'X-MGS-CSRF',
        'X-Signature',
        'X-Webhook-Signature',
    ],
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
        'X-Idempotent-Replay',
    ],
    'max_age' => (int) env('CORS_MAX_AGE', 600),
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),
];
