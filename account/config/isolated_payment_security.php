<?php

/**
 * Security Configuration for Isolated Payment Module
 * 
 * This configuration provides maximum security settings for the isolated payment processor.
 * All settings are managed through the admin panel - do not edit manually.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Isolated Payment Module Security Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the security isolation layer for third-party
    | payment processors. Adjust through admin panel only.
    |
    */

    // Enable/disable isolated payment module (default: false - disabled by default)
    'enabled' => env('ISOLATED_PAYMENT_ENABLED', false),

    // Maximum security level (STRICT, MODERATE, PERMISSIVE)
    'security_level' => env('ISOLATED_PAYMENT_SECURITY_LEVEL', 'STRICT'),

    /*
    |--------------------------------------------------------------------------
    | Network Security
    |--------------------------------------------------------------------------
    */

    // Blocked IP ranges (CIDR notation)
    'blocked_ip_ranges' => [
        '10.0.0.0/8',      // Private network A
        '172.16.0.0/12',   // Private network B
        '192.168.0.0/16',  // Private network C
        '127.0.0.0/8',     // Loopback
        '0.0.0.0/8',       // Invalid
        '169.254.0.0/16',  // Link-local
    ],

    // Known datacenter IP ranges (often used for bots/attacks)
    'datacenter_ip_ranges' => [
        // Add known hosting provider ranges here if needed
        // Example: '104.16.0.0/12', // Cloudflare
    ],

    // Allowed callback URLs (whitelist only)
    'allowed_callback_domains' => [
        'account.ygxone.com',
        'pay.ygxone.com',
        // Add your production domains here
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    // Maximum payment requests per IP per hour
    'payment_rate_limit' => env('ISOLATED_PAYMENT_RATE_LIMIT', 100),

    // Rate limit window in minutes
    'payment_rate_window' => env('ISOLATED_PAYMENT_RATE_WINDOW', 60),

    // Maximum failed attempts before temporary block
    'max_failed_attempts' => env('ISOLATED_PAYMENT_MAX_FAILED', 10),

    // Block duration after max failed attempts (minutes)
    'block_duration_minutes' => env('ISOLATED_PAYMENT_BLOCK_DURATION', 30),

    /*
    |--------------------------------------------------------------------------
    | Payload Restrictions
    |--------------------------------------------------------------------------
    */

    // Maximum request payload size in bytes (1MB default)
    'max_payload_size' => env('ISOLATED_PAYMENT_MAX_PAYLOAD', 1048576),

    // Maximum transaction amount
    'max_transaction_amount' => env('ISOLATED_PAYMENT_MAX_AMOUNT', 999999.99),

    // Minimum transaction amount
    'min_transaction_amount' => env('ISOLATED_PAYMENT_MIN_AMOUNT', 0.01),

    // Allowed currencies
    'allowed_currencies' => [
        'NPR',  // Nepalese Rupee
        'USD',  // US Dollar
        'EUR',  // Euro
        'INR',  // Indian Rupee
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation
    |--------------------------------------------------------------------------
    */

    // Enable strict input validation
    'strict_input_validation' => env('ISOLATED_PAYMENT_STRICT_VALIDATION', true),

    // Sanitize all string inputs
    'sanitize_inputs' => env('ISOLATED_PAYMENT_SANITIZE_INPUTS', true),

    // Block SQL injection patterns
    'block_sql_injection' => env('ISOLATED_PAYMENT_BLOCK_SQLI', true),

    // Block XSS patterns
    'block_xss' => env('ISOLATED_PAYMENT_BLOCK_XSS', true),

    // Block command injection patterns
    'block_command_injection' => env('ISOLATED_PAYMENT_BLOCK_CMDI', true),

    /*
    |--------------------------------------------------------------------------
    | Logging & Monitoring
    |--------------------------------------------------------------------------
    */

    // Enable comprehensive security logging
    'enable_security_logging' => env('ISOLATED_PAYMENT_LOGGING', true),

    // Log level for security events (debug, info, warning, error, critical)
    'log_level' => env('ISOLATED_PAYMENT_LOG_LEVEL', 'info'),

    // Store logs for X days
    'log_retention_days' => env('ISOLATED_PAYMENT_LOG_RETENTION', 90),

    // Alert on suspicious activity
    'alert_on_suspicious' => env('ISOLATED_PAYMENT_ALERTS', true),

    // Email addresses for security alerts
    'alert_emails' => explode(',', env('ISOLATED_PAYMENT_ALERT_EMAILS', 'admin@ygxone.com')),

    /*
    |--------------------------------------------------------------------------
    | File Integrity Monitoring
    |--------------------------------------------------------------------------
    */

    // Enable file integrity checking
    'enable_integrity_check' => env('ISOLATED_PAYMENT_INTEGRITY_CHECK', true),

    // Check interval in seconds (3600 = 1 hour)
    'integrity_check_interval' => env('ISOLATED_PAYMENT_INTEGRITY_INTERVAL', 3600),

    // Critical files to monitor
    'monitored_files' => [
        'index.php',
        'config/app.php',
        'routes/api.php',
        '.env',
    ],

    // Auto-block on integrity violation
    'block_on_integrity_violation' => env('ISOLATED_PAYMENT_BLOCK_ON_VIOLATION', true),

    /*
    |--------------------------------------------------------------------------
    | SSL/TLS Configuration
    |--------------------------------------------------------------------------
    */

    // Enforce HTTPS
    'enforce_https' => env('ISOLATED_PAYMENT_ENFORCE_HTTPS', true),

    // SSL certificate verification
    'verify_ssl' => env('ISOLATED_PAYMENT_VERIFY_SSL', true),

    // Minimum TLS version
    'min_tls_version' => env('ISOLATED_PAYMENT_MIN_TLS', '1.2'),

    /*
    |--------------------------------------------------------------------------
    | Timeout Settings
    |--------------------------------------------------------------------------
    */

    // Request timeout in seconds
    'request_timeout' => env('ISOLATED_PAYMENT_TIMEOUT', 30),

    // Connection timeout in seconds
    'connection_timeout' => env('ISOLATED_PAYMENT_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Emergency Controls
    |--------------------------------------------------------------------------
    */

    // Emergency kill switch - immediately disable all isolated payments
    'emergency_disable' => env('ISOLATED_PAYMENT_EMERGENCY_DISABLE', false),

    // Maintenance mode
    'maintenance_mode' => env('ISOLATED_PAYMENT_MAINTENANCE', false),

    // Maintenance message
    'maintenance_message' => env('ISOLATED_PAYMENT_MAINTENANCE_MESSAGE', 
        'Payment processing is temporarily unavailable. Please try again later.'),

    /*
    |--------------------------------------------------------------------------
    | Compliance Settings
    |--------------------------------------------------------------------------
    */

    // PCI DSS compliance mode (disables certain features if non-compliant)
    'pci_compliance_mode' => env('ISOLATED_PAYMENT_PCI_MODE', false),

    // GDPR data retention (days)
    'gdpr_retention_days' => env('ISOLATED_PAYMENT_GDPR_RETENTION', 365),

    // Anonymize logs after retention period
    'anonymize_old_logs' => env('ISOLATED_PAYMENT_ANONYMIZE_LOGS', true),

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */

    // Path to isolated payment module
    'isolated_module_path' => env('ISOLATED_PAYMENT_MODULE_PATH', 
        base_path('YG Pay/qrpaypro-web/ygpay/ygpay')),

    // API endpoint for isolated module
    'api_endpoint' => env('ISOLATED_PAYMENT_API_ENDPOINT', '/ygpay/api/process'),

    // Webhook URL for callbacks
    'webhook_url' => env('ISOLATED_PAYMENT_WEBHOOK_URL', '/api/payment/webhook/isolated'),

    /*
    |--------------------------------------------------------------------------
    | Advanced Security Features
    |--------------------------------------------------------------------------
    */

    // Enable honeypot fields to detect bots
    'enable_honeypot' => env('ISOLATED_PAYMENT_HONEYPOT', true),

    // Enable CAPTCHA for high-risk transactions
    'enable_captcha' => env('ISOLATED_PAYMENT_CAPTCHA', false),

    // CAPTCHA threshold (transaction amount above which CAPTCHA is required)
    'captcha_threshold' => env('ISOLATED_PAYMENT_CAPTCHA_THRESHOLD', 10000),

    // Enable device fingerprinting
    'enable_device_fingerprint' => env('ISOLATED_PAYMENT_DEVICE_FINGERPRINT', true),

    // Enable behavioral analysis
    'enable_behavioral_analysis' => env('ISOLATED_PAYMENT_BEHAVIORAL', false),

    // Suspicious behavior thresholds
    'behavioral_thresholds' => [
        'rapid_requests' => 10,      // requests per minute
        'failed_attempts' => 5,      // consecutive failures
        'unusual_hours' => true,     // flag transactions outside business hours
    ],
];
