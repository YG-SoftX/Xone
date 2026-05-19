<?php

return [

    /*
    |--------------------------------------------------------------------------
    | YG Account SSO
    |--------------------------------------------------------------------------
    |
    | These values configure the Single Sign-On integration with the YG Account
    | platform. All YGXONE modules share the same Account backend for auth.
    |
    */

    'yg_account' => [
        'url'      => env('YG_ACCOUNT_URL', 'https://account.ygxone.com'),
        'api_base' => env('YG_ACCOUNT_API_BASE', 'https://account.ygxone.com'),
    ],

];
