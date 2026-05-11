<?php

return [
    /*
    |--------------------------------------------------------------------------
    | YG Pay Integration
    |--------------------------------------------------------------------------
    */
    'yg_pay' => [
        'url' => env('YG_PAY_URL', 'https://pay.ygxone.com'),
        'api_key' => env('YG_PAY_API_KEY'),
        'webhook_secret' => env('YG_PAY_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG AI Integration
    |--------------------------------------------------------------------------
    */
    'yg_ai' => [
        'url' => env('YG_AI_URL', 'https://ai.ygxone.com'),
        'api_key' => env('YG_AI_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Account SSO
    |--------------------------------------------------------------------------
    */
    'yg_account' => [
        'url' => env('YG_ACCOUNT_URL', 'https://account.ygxone.com'),
        'client_id' => env('YG_ACCOUNT_CLIENT_ID'),
        'client_secret' => env('YG_ACCOUNT_CLIENT_SECRET'),
        'redirect_uri' => env('YG_ACCOUNT_REDIRECT_URI', 'https://console.ygxone.com/auth/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Drive Storage
    |--------------------------------------------------------------------------
    */
    'yg_drive' => [
        'url' => env('YG_DRIVE_URL', 'https://drive.ygxone.com'),
        'api_key' => env('YG_DRIVE_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Master Integration
    |--------------------------------------------------------------------------
    */
    'yg_master' => [
        'url' => env('YG_MASTER_URL', 'https://master.ygxone.com'),
        'api_key' => env('YG_MASTER_API_KEY'),
        'webhook_secret' => env('YG_MASTER_WEBHOOK_SECRET'),
        'timeout' => env('YG_MASTER_TIMEOUT', 10),
    ],

];
