<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Email Addresses
    |--------------------------------------------------------------------------
    | Comma-separated list of email addresses granted admin access in addition
    | to user ID 1. Set ADMIN_EMAILS in your .env file.
    */
    'emails' => env('ADMIN_EMAILS', 'admin@ygxone.com'),

    /*
    |--------------------------------------------------------------------------
    | Admin Session Timeout (minutes)
    |--------------------------------------------------------------------------
    | Idle admin sessions are invalidated after this many minutes of inactivity.
    */
    'session_timeout' => (int) env('ADMIN_SESSION_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Login Rate Limiting
    |--------------------------------------------------------------------------
    | Max failed attempts before a lockout, and how long the lockout lasts.
    */
    'max_login_attempts' => (int) env('ADMIN_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_minutes'    => (int) env('ADMIN_LOCKOUT_MINUTES', 15),

];
