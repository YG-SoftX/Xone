<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('YG_ACCOUNT_URL', 'https://account.ygxone.com'),
        env('YG_MAIL_URL',    'https://mail.ygxone.com'),
        env('YG_DOCX_URL',   'https://docx.ygxone.com'),
        env('YG_MASTER_URL', 'https://master.ygxone.com'),
        'https://ygxone.com',
        'https://www.ygxone.com',
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
