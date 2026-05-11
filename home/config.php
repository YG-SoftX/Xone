<?php
/**
 * YG Home — Configuration
 * 
 * This file configures the YG Home frontend (ygxone.com)
 * and its connections to other YG services.
 */

return [
    // ── YG AI Search Engine ───────────────────────────────────────────
    // Path to YG AI's root directory (for local file access)
    // In production, set via environment variable YG_AI_ROOT
    'yg_ai_root' => getenv('YG_AI_ROOT') ?: dirname(__DIR__, 2) . '/Productivity/yg-ai',

    // HTTP API URL (optional — if set, uses HTTP instead of local files)
    // Set via YG_AI_API_URL for cross-server deployments
    'yg_ai_api_url' => getenv('YG_AI_API_URL') ?: null,

    // ── YG Account (SSO / Identity) ──────────────────────────────────
    'account_url' => getenv('YG_ACCOUNT_URL') ?: 'https://account.ygxone.com',

    // ── Ecosystem Service URLs ────────────────────────────────────────
    'services' => [
        'mail'       => getenv('YG_MAIL_URL')       ?: 'https://mail.ygxone.com',
        'drive'      => getenv('YG_DRIVE_URL')      ?: 'https://drive.ygxone.com',
        'docx'       => getenv('YG_DOCX_URL')       ?: 'https://docx.ygxone.com',
        'meet'       => getenv('YG_MEET_URL')       ?: 'https://meet.ygxone.com',
        'chat'       => getenv('YG_CHAT_URL')       ?: 'https://chat.ygxone.com',
        'pay'        => getenv('YG_PAY_URL')        ?: 'https://pay.ygxone.com',
        'master'     => getenv('YG_MASTER_URL')     ?: 'https://master.ygxone.com',
        'calendar'   => getenv('YG_CALENDAR_URL')   ?: 'https://calendar.ygxone.com',
        'contacts'   => getenv('YG_CONTACTS_URL')   ?: 'https://contacts.ygxone.com',
        'notes'      => getenv('YG_NOTES_URL')      ?: 'https://notes.ygxone.com',
        'news'       => getenv('YG_NEWS_URL')       ?: 'https://news.ygxone.com',
    ],

    // ── Site Info ─────────────────────────────────────────────────────
    'name' => 'YGXONE',
    'description' => 'The world\'s first sovereign digital suite.',
    'url' => getenv('APP_URL') ?: 'https://ygxone.com',
];
