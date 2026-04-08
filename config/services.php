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

    'billplz' => [
        'api_key' => env('BILLPLZ_API_KEY'),
        'x_signature' => env('BILLPLZ_X_SIGNATURE'),
        'collection_id' => env('BILLPLZ_COLLECTION_ID'),
        'base_url' => env('BILLPLZ_BASE_URL', 'https://www.billplz.com/api/v3'),
        'card_gateway_code' => env('BILLPLZ_CARD_GATEWAY_CODE', 'BP-BILLPLZ1'),
    ],

    'sendparcel' => [
        'base_url' => env('SENDPARCEL_BASE_URL'),
        'api_key' => env('SENDPARCEL_API_KEY'),
    ],
];
