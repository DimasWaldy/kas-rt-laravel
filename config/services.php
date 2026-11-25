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

    'whatsapp' => [
        'url' => env('WHATSAPP_API_URL'),
        'token' => env('WHATSAPP_API_TOKEN'),
        'from' => env('WHATSAPP_FROM_NUMBER'),
    ],

    'gemini' => [
        'keys' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEMINI_API_KEYS', (string) env('GEMINI_API_KEY', '')))
        ))),
        'models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEMINI_MODELS', 'gemini-3.5-flash,gemini-3.1-flash-lite,gemini-3.8-flash'))
        ))),
        'base_url' => rtrim((string) env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 15),
        'cooldown_seconds' => (int) env('GEMINI_KEY_COOLDOWN_SECONDS', 60),
    ],
];
