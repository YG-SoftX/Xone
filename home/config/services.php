<?php

return [

    /*
    |--------------------------------------------------------------------------
    | YG AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for YG AI service integration (your own AI platform)
    |
    */

    'yg_ai' => [
        'api_url' => env('YG_AI_URL', 'https://ai.ygxone.com/api'),
        'api_key' => env('YG_AI_API_KEY', ''),
        'public_key' => env('YG_AI_PUBLIC_KEY', ''),
        'timeout' => env('YG_AI_TIMEOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Account Configuration
    |--------------------------------------------------------------------------
    */

    'yg_account' => [
        'url' => env('YG_ACCOUNT_URL', 'https://account.ygxone.com'),
        'api_key' => env('YG_ACCOUNT_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Mail Configuration
    |--------------------------------------------------------------------------
    */

    'yg_mail' => [
        'url' => env('YG_MAIL_URL', 'https://mail.ygxone.com'),
        'api_key' => env('YG_MAIL_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Drive Configuration
    |--------------------------------------------------------------------------
    */

    'yg_drive' => [
        'url' => env('YG_DRIVE_URL', 'https://drive.ygxone.com'),
        'api_key' => env('YG_DRIVE_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG DocX Configuration
    |--------------------------------------------------------------------------
    */

    'yg_docx' => [
        'url' => env('YG_DOCX_URL', 'https://docs.ygxone.com'),
        'api_key' => env('YG_DOCX_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Calendar Configuration
    |--------------------------------------------------------------------------
    */

    'yg_calendar' => [
        'url' => env('YG_CALENDAR_URL', 'https://calendar.ygxone.com'),
        'api_key' => env('YG_CALENDAR_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Contacts Configuration
    |--------------------------------------------------------------------------
    */

    'yg_contacts' => [
        'url' => env('YG_CONTACTS_URL', 'https://contacts.ygxone.com'),
        'api_key' => env('YG_CONTACTS_API_KEY', ''),
    ],

];
