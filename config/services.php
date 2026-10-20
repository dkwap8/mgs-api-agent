<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'provider' => [
        'base_url' => env('PROVIDER_BASE_URL'),
        'api_key' => env('PROVIDER_API_KEY'),
        'secret_key' => env('PROVIDER_SECRET_KEY'),
        'webhook_secret' => env('PROVIDER_WEBHOOK_SECRET'),
        'timeout' => (int) env('PROVIDER_TIMEOUT', 30),
    ],

    'webhooks' => [
        'signature_header' => env('WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
        'timestamp_tolerance' => (int) env('WEBHOOK_TIMESTAMP_TOLERANCE', 300),
        'delivery_timeout' => (int) env('WEBHOOK_DELIVERY_TIMEOUT', 15),
        'delivery_attempts' => (int) env('WEBHOOK_DELIVERY_ATTEMPTS', 5),
    ],

    'security' => [
        'withdrawal_otp_ttl' => (int) env('WITHDRAWAL_OTP_TTL', 10),
        'registration_otp_ttl' => (int) env('REGISTRATION_OTP_TTL', 10),
        'max_login_attempts' => (int) env('MAX_LOGIN_ATTEMPTS', 5),
    ],

];
