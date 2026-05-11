<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Escalation Policies
    |--------------------------------------------------------------------------
    |
    | Define multi-tier escalation policies for different incident types.
    | Each policy has steps with increasing urgency and different notification channels.
    |
    */
    'policies' => [
        // Critical system failures (backup, scheduler, database jobs)
        [
            'id' => 'critical-system-failure',
            'name' => 'Critical System Failure Escalation',
            'severities' => ['critical'],
            'job_patterns' => ['backup', 'scheduler', 'database', 'laravel-scheduler'],
            'steps' => [
                [
                    'level' => 1,
                    'description' => 'Notify on-call engineer via SMS and PagerDuty',
                    'channels' => ['sms', 'pagerduty'],
                    'timeout_minutes' => 5,
                    'recipients' => explode(',', env('TWILIO_ADMIN_PHONES', '')),
                ],
                [
                    'level' => 2,
                    'description' => 'Escalate to team lead with phone call',
                    'channels' => ['phone_call', 'email', 'slack'],
                    'timeout_minutes' => 10,
                    'recipients' => [env('TWILIO_ONCALL_PHONE')],
                ],
                [
                    'level' => 3,
                    'description' => 'Emergency escalation to engineering manager',
                    'channels' => ['phone_call', 'sms', 'email'],
                    'timeout_minutes' => 15,
                    'recipients' => [env('TWILIO_MANAGER_PHONE')],
                ],
            ],
        ],

        // High priority incidents
        [
            'id' => 'high-priority-incident',
            'name' => 'High Priority Incident Escalation',
            'severities' => ['high'],
            'steps' => [
                [
                    'level' => 1,
                    'description' => 'Notify on-call engineer',
                    'channels' => ['sms', 'pagerduty'],
                    'timeout_minutes' => 10,
                ],
                [
                    'level' => 2,
                    'description' => 'Escalate to team lead',
                    'channels' => ['email', 'slack'],
                    'timeout_minutes' => 20,
                ],
            ],
        ],

        // Standard escalation for medium/low severity
        [
            'id' => 'standard-escalation',
            'name' => 'Standard Escalation Policy',
            'severities' => ['medium', 'low'],
            'steps' => [
                [
                    'level' => 1,
                    'description' => 'Email notification to admin team',
                    'channels' => ['email'],
                    'timeout_minutes' => 60,
                ],
            ],
        ],

        // Business hours only (no night/weekend escalations)
        [
            'id' => 'business-hours-only',
            'name' => 'Business Hours Escalation',
            'severities' => ['medium'],
            'time_restriction' => [
                'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17], // 9 AM - 5 PM
            ],
            'steps' => [
                [
                    'level' => 1,
                    'description' => 'Slack notification to team channel',
                    'channels' => ['slack'],
                    'timeout_minutes' => 30,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Escalation Settings
    |--------------------------------------------------------------------------
    */
    'max_escalation_levels' => 5,
    'default_timeout_minutes' => 15,
    'enable_phone_calls' => env('ESCALATION_ENABLE_PHONE_CALLS', false),
    'business_hours_timezone' => env('APP_TIMEZONE', 'UTC'),
];
