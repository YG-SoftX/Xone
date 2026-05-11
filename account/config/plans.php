<?php

/**
 * YGXone Plan Definitions
 *
 * personal  — free forever (individual users)
 * business  — $8/user/month (teams, custom domains, admin panel)
 * enterprise— $18/user/month (SLA, unlimited storage, audit logs)
 *
 * Features are checked by PlanService::can($user, 'feature_key').
 */

return [

    'personal' => [
        'name'              => 'Personal',
        'price_monthly'     => 0,
        'storage_bytes'     => 15 * 1024 ** 3,   // 15 GB
        'mail_daily_limit'  => 100,
        'ai_queries_daily'  => 50,
        'features'          => [
            'mail'          => true,
            'drive'         => true,
            'docx'          => true,
            'xcel'          => true,
            'meet'          => true,  // up to 30 min
            'pay'           => true,
            'ai'            => true,
            'forms'         => true,
            'custom_domain' => false,
            'admin_panel'   => false,
            'team_drive'    => false,
            'audit_logs'    => false,
            'api_access'    => false,
            'priority_support' => false,
        ],
    ],

    'business' => [
        'name'              => 'Business',
        'price_monthly'     => 8,                 // per user
        'storage_bytes'     => 100 * 1024 ** 3,  // 100 GB per user
        'mail_daily_limit'  => 2000,
        'ai_queries_daily'  => 500,
        'features'          => [
            'mail'          => true,
            'drive'         => true,
            'docx'          => true,
            'xcel'          => true,
            'meet'          => true,  // unlimited
            'pay'           => true,
            'ai'            => true,
            'forms'         => true,
            'custom_domain' => true,
            'admin_panel'   => true,
            'team_drive'    => true,
            'audit_logs'    => false,
            'api_access'    => true,
            'priority_support' => false,
        ],
    ],

    'enterprise' => [
        'name'              => 'Enterprise',
        'price_monthly'     => 18,                // per user
        'storage_bytes'     => 1024 * 1024 ** 3, // 1 TB per user
        'mail_daily_limit'  => 10000,
        'ai_queries_daily'  => 2000,
        'features'          => [
            'mail'          => true,
            'drive'         => true,
            'docx'          => true,
            'xcel'          => true,
            'meet'          => true,
            'pay'           => true,
            'ai'            => true,
            'forms'         => true,
            'custom_domain' => true,
            'admin_panel'   => true,
            'team_drive'    => true,
            'audit_logs'    => true,
            'api_access'    => true,
            'priority_support' => true,
        ],
    ],

    // ── Storage add-on packs (available to personal users) ─────────────────
    'storage_packs' => [
        '100gb'  => ['bytes' => 100 * 1024 ** 3,  'price_monthly' => 2.00,  'label' => '100 GB'],
        '200gb'  => ['bytes' => 200 * 1024 ** 3,  'price_monthly' => 3.00,  'label' => '200 GB'],
        '2tb'    => ['bytes' => 2 * 1024 ** 4,    'price_monthly' => 10.00, 'label' => '2 TB'],
    ],
];
