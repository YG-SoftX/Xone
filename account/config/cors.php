<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'https://account.ygxone.com',
        'https://mail.ygxone.com',
        'https://drive.ygxone.com',
        'https://docx.ygxone.com',
        'https://meet.ygxone.com',
        'https://chat.ygxone.com',
        'https://pay.ygxone.com',
        'https://master.ygxone.com',
        'https://ygxone.com',
        'https://www.ygxone.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN', 'X-API-Key'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
