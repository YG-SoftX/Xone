<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Multi-provider payment system with automatic fallback.
    | Priority: Stripe → PayPal → Razorpay
    |
    */

    // Default provider - YG Pay (internal service)
    'default' => env('PAYMENT_DEFAULT_PROVIDER', 'ygpay'),

    // Enable/disable providers - YG Pay is now PRIMARY
    'providers' => [
        'ygpay' => env('PAYMENT_YGPAY_ENABLED', true),  // PRIMARY - Internal YG Pay service
        'stripe' => env('PAYMENT_STRIPE_ENABLED', false),   // Disabled - handled by YG Pay
        'paypal' => env('PAYMENT_PAYPAL_ENABLED', false),   // Disabled - handled by YG Pay
        'razorpay' => env('PAYMENT_RAZORPAY_ENABLED', false), // Disabled - handled by YG Pay
        'connectips' => env('PAYMENT_CONNECTIPS_ENABLED', false), // Disabled - handled by YG Pay
        'fonepay' => env('PAYMENT_FONEPAY_ENABLED', false),     // Disabled - handled by YG Pay
        'upi' => env('PAYMENT_UPI_ENABLED', false),             // Disabled - handled by YG Pay
        'truelayer' => env('PAYMENT_TRUELAYER_ENABLED', false), // Disabled - handled by YG Pay
        'khalti' => env('PAYMENT_KHALTI_ENABLED', false),       // Disabled - handled by YG Pay
        'esewa' => env('PAYMENT_ESEWA_ENABLED', false),         // Disabled - handled by YG Pay
    ],

    /*
    |--------------------------------------------------------------------------
    | ConnectIPS Configuration
    |--------------------------------------------------------------------------
    */
    'connectips' => [
        'merchant_id' => env('CONNECTIPS_MERCHANT_ID'),
        'app_id' => env('CONNECTIPS_APP_ID'),
        'app_name' => env('CONNECTIPS_APP_NAME'),
        'password' => env('CONNECTIPS_PASSWORD'),
        'pfx_path' => env('CONNECTIPS_PFX_PATH'),
        'pfx_password' => env('CONNECTIPS_PFX_PASSWORD'),
        'api_url' => env('CONNECTIPS_API_URL', 'https://uat.connectips.com:7443/connectipswebgw/api/v2/epayment'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fonepay Configuration
    |--------------------------------------------------------------------------
    */
    'fonepay' => [
        'merchant_code' => env('FONEPAY_MERCHANT_CODE'),
        'secret_key' => env('FONEPAY_SECRET_KEY'),
        'api_url' => env('FONEPAY_API_URL', 'https://dev-api.fonepay.com/api/merchantRequest'),
    ],

    /*
    |--------------------------------------------------------------------------
    | UPI Configuration (Direct)
    |--------------------------------------------------------------------------
    */
    'upi' => [
        'vpa' => env('UPI_VPA'),
        'merchant_name' => env('UPI_MERCHANT_NAME'),
        'merchant_code' => env('UPI_MERCHANT_CODE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TrueLayer Configuration
    |--------------------------------------------------------------------------
    */
    'truelayer' => [
        'client_id' => env('TRUELAYER_CLIENT_ID'),
        'client_secret' => env('TRUELAYER_CLIENT_SECRET'),
        'key_id' => env('TRUELAYER_KEY_ID'),
        'private_key_path' => env('TRUELAYER_PRIVATE_KEY_PATH'),
        'api_url' => env('TRUELAYER_API_URL', 'https://api.truelayer.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Khalti Configuration
    |--------------------------------------------------------------------------
    */
    'khalti' => [
        'public_key' => env('KHALTI_PUBLIC_KEY'),
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'api_url' => env('KHALTI_API_URL', 'https://khalti.com/api/v2/payment/verify/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | YG Pay Configuration (Internal)
    |--------------------------------------------------------------------------
    */
    'ygpay' => [
        'api_key' => env('YGPAY_API_KEY'),
        'api_url' => env('YGPAY_API_URL', 'https://pay.ygxone.com/api/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'USD'),
        
        // Payment methods to enable
        'payment_methods' => [
            'card',
            'apple_pay',
            'google_pay',
        ],

        // Webhook events to listen for
        'webhook_events' => [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'charge.refunded',
            'invoice.payment_succeeded',
            'invoice.payment_failed',
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    */
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'currency' => env('PAYPAL_CURRENCY', 'USD'),
        // Webhook ID from PayPal Dashboard — required for signature verification
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),

        // PayPal API endpoints
        'api_url' => env('PAYPAL_MODE') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Razorpay Configuration
    |--------------------------------------------------------------------------
    */
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'currency' => env('RAZORPAY_CURRENCY', 'INR'),
        
        // Webhook configuration
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        
        // Supported payment methods
        'payment_methods' => [
            'card',
            'netbanking',
            'upi',
            'wallet',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | General Payment Settings
    |--------------------------------------------------------------------------
    */
    
    // Currency conversion settings
    'currency_conversion' => [
        'enabled' => env('CURRENCY_CONVERSION_ENABLED', false),
        'base_currency' => env('BASE_CURRENCY', 'USD'),
    ],

    // Transaction settings
    'transaction' => [
        'retry_attempts' => env('PAYMENT_RETRY_ATTEMPTS', 3),
        'timeout_seconds' => env('PAYMENT_TIMEOUT', 30),
        'min_amount' => env('PAYMENT_MIN_AMOUNT', 1.00),
        'max_amount' => env('PAYMENT_MAX_AMOUNT', 999999.99),
    ],

    // Webhook settings
    'webhook' => [
        'signature_header' => 'X-Payment-Signature',
        'ip_whitelist' => array_filter(explode(',', env('PAYMENT_WEBHOOK_IPS', ''))),
        'verify_signature' => env('PAYMENT_VERIFY_SIGNATURE', true),
    ],

    // Logging
    'logging' => [
        'log_all_transactions' => env('PAYMENT_LOG_TRANSACTIONS', true),
        'log_level' => env('PAYMENT_LOG_LEVEL', 'info'),
    ],

];
