<?php

return [
    'default' => env('COURIER_PROVIDER', 'sendparcelpro'),

    'sendparcelpro' => [
        'driver' => env('SENDPARCEL_DRIVER', 'fake'),
        'base_url' => env('SENDPARCEL_BASE_URL'),
        'client_id' => env('SENDPARCEL_CLIENT_ID'),
        'client_secret' => env('SENDPARCEL_CLIENT_SECRET'),
    ],
];