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

    'simulador_online' => [
        'base_url' => env('SIMULADOR_ONLINE_BASE_URL', 'https://app.simuladoronline.com'),
        'login_path' => env('SIMULADOR_ONLINE_LOGIN_PATH', '/login/7789'),
        'corretor_email' => env('SIMULADOR_ONLINE_CORRETOR_EMAIL', ''),
        'username' => env('SIMULADOR_ONLINE_USERNAME'),
        'password' => env('SIMULADOR_ONLINE_PASSWORD'),
        'verify_ssl' => env('SIMULADOR_ONLINE_VERIFY_SSL', false),
    ],

    'uazapi' => [
        'base_url' => env('UAZAPI_BASE_URL', 'http://localhost:8080'),
        'token' => env('UAZAPI_TOKEN'),
        'instance' => env('UAZAPI_INSTANCE'),
    ],

];
