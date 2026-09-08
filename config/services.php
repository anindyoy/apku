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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'harga_emas' => [
        'url' => env('HARGA_EMAS_URL', 'https://logam-mulia-api.iamutaki.workers.dev/api/prices/anekalogam'),
        'timeout' => env('HARGA_EMAS_TIMEOUT', 8),
        'cache_hours' => env('HARGA_EMAS_CACHE_HOURS', 3),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
        'hostnames' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('TURNSTILE_HOSTNAMES', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')),
        ))),
        'action' => 'register',
        'timeout' => env('TURNSTILE_TIMEOUT', 5),
    ],

];
