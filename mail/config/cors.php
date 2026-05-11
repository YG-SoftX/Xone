<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('VITE_YG_ACCOUNT_URL', 'http://localhost:8000'),
        env('VITE_YG_DRIVE_URL', 'http://localhost:3007'),
        env('VITE_YG_DOCX_URL', 'http://localhost:8003'),
        env('VITE_YG_MEET_URL', 'http://localhost:3009'),
        env('VITE_YG_CHAT_URL', 'http://localhost:8006'),
        env('VITE_YG_PAY_URL', 'http://localhost:3001'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
