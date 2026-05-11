<?php
return [
    'paths' => ['api/*', 'ai/*'],
    'allowed_methods' => ['*'],
    // Adjust origins to specific domains in production
    'allowed_origins' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
