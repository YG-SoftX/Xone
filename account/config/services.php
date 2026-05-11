<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'yg_account' => [
        'url' => env('YG_ACCOUNT_URL', 'http://localhost:8000'),
    ],

    'yg_services' => [
        'mail' => env('VITE_YG_MAIL_URL', 'http://localhost:3002'),
        'drive' => env('VITE_YG_DRIVE_URL', 'http://localhost:3007'),
        'docx' => env('VITE_YG_DOCX_URL', 'http://localhost:8003'),
        'meet' => env('VITE_YG_MEET_URL', 'http://localhost:3009'),
        'chat' => env('VITE_YG_CHAT_URL', 'http://localhost:8006'),
        'pay' => env('VITE_YG_PAY_URL', 'http://localhost:3001'),
        'master' => env('VITE_YG_MASTER_URL', 'http://localhost:8009'),
    ],

    'yg_ai' => [
        'url' => env('YG_AI_URL', 'https://ai.ygxone.com'),
        'api_key' => env('YG_AI_API_KEY', null),
        'timeout' => env('YG_AI_TIMEOUT', 5),
    ],

    // Twilio SMS notifications
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ],

    // Firebase Cloud Messaging for push notifications
    'firebase' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

    // Grafana analytics integration
    'grafana' => [
        'influxdb_url' => env('INFLUXDB_URL', 'http://localhost:8086'),
        'influxdb_token' => env('INFLUXDB_TOKEN'),
        'influxdb_bucket' => env('INFLUXDB_BUCKET', 'cron_metrics'),
        'influxdb_org' => env('INFLUXDB_ORG', 'yg_account'),
        'dashboard_url' => env('GRAFANA_DASHBOARD_URL', 'http://localhost:3000'),
    ],

    // PagerDuty incident management
    'pagerduty' => [
        'api_key' => env('PAGERDUTY_API_KEY'),
        'service_id' => env('PAGERDUTY_SERVICE_ID'),
        'routing_key' => env('PAGERDUTY_ROUTING_KEY'),
    ],

    // Custom webhooks for third-party integrations
    'webhooks' => [
        'cron_alerts' => [
            [
                'platform' => env('CRON_ALERT_SLACK_PLATFORM', 'slack'),
                'url' => env('CRON_ALERT_SLACK_WEBHOOK', ''),
                'enabled' => env('CRON_ALERT_SLACK_ENABLED', false),
            ],
            [
                'platform' => env('CRON_ALERT_DISCORD_PLATFORM', 'discord'),
                'url' => env('CRON_ALERT_DISCORD_WEBHOOK', ''),
                'enabled' => env('CRON_ALERT_DISCORD_ENABLED', false),
            ],
        ],
        'custom' => [
            // Example custom webhook configurations
            // [
            //     'id' => 'zapier-integration',
            //     'url' => 'https://hooks.zapier.com/hooks/catch/123456/abcdef/',
            //     'secret' => env('ZAPIER_WEBHOOK_SECRET'),
            //     'events' => ['cron_job.failed', 'incident.created'],
            //     'enabled' => true,
            //     'max_retries' => 3,
            //     'custom_headers' => [],
            // ],
        ],
    ],

];
