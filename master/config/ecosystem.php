<?php

/**
 * YGXone Ecosystem — App Registry Configuration
 *
 * Set the ECOSYSTEM_ROOT env var to the directory that contains all app folders.
 * On cPanel this is typically /home/cpaneluser/
 *
 * Individual paths override ECOSYSTEM_ROOT if set.
 */

$root = rtrim(env('ECOSYSTEM_ROOT', '/home/cpaneluser'), '/');

return [

    // ── Runtime tools ──────────────────────────────────────────────────────────
    'php'      => env('ECOSYSTEM_PHP',      'php'),
    'composer' => env('ECOSYSTEM_COMPOSER', 'composer'),
    'npm'      => env('ECOSYSTEM_NPM',      'npm'),
    'lib'      => env('ECOSYSTEM_LIB',      $root . '/ecosystem/lib/update-lib.sh'),

    // ── Health check timeout (seconds) ─────────────────────────────────────────
    'health_timeout' => (int) env('ECOSYSTEM_HEALTH_TIMEOUT', 8),

    // ── App registry ───────────────────────────────────────────────────────────
    // type: 'laravel' | 'static'
    // health: endpoint appended to url to verify the app is alive
    'apps' => [

        'yg-xone' => [
            'name'   => 'YG Xone',
            'path'   => env('ECOSYSTEM_PATH_XONE',      $root . '/public_html'),
            'type'   => 'static',
            'url'    => env('YG_XONE_URL',              'https://ygxone.com'),
            'health' => '/',
            'icon'   => '🌐',
            'order'  => 1,
        ],

        'yg-account' => [
            'name'   => 'YG Account',
            'path'   => env('ECOSYSTEM_PATH_ACCOUNT',   $root . '/yg-account'),
            'type'   => 'laravel',
            'url'    => env('YG_ACCOUNT_URL',           'https://account.ygxone.com'),
            'health' => '/up',
            'icon'   => '🔐',
            'order'  => 2,
        ],

        'yg-master' => [
            'name'   => 'YG Master',
            'path'   => env('ECOSYSTEM_PATH_MASTER',    $root . '/yg-master'),
            'type'   => 'laravel',
            'url'    => env('APP_URL',                  'https://master.ygxone.com'),
            'health' => '/up',
            'icon'   => '⚙️',
            'order'  => 3,
        ],

        'yg-mail' => [
            'name'   => 'YG Mail',
            'path'   => env('ECOSYSTEM_PATH_MAIL',      $root . '/yg-mail'),
            'type'   => 'laravel',
            'url'    => env('YG_MAIL_URL',              'https://mail.ygxone.com'),
            'health' => '/up',
            'icon'   => '✉️',
            'order'  => 4,
        ],

        'yg-drive' => [
            'name'   => 'YG Drive',
            'path'   => env('ECOSYSTEM_PATH_DRIVE',     $root . '/yg-drive'),
            'type'   => 'laravel',
            'url'    => env('YG_DRIVE_URL',             'https://drive.ygxone.com'),
            'health' => '/up',
            'icon'   => '💾',
            'order'  => 5,
        ],

        'yg-docx' => [
            'name'   => 'YG DocX',
            'path'   => env('ECOSYSTEM_PATH_DOCX',      $root . '/yg-docx'),
            'type'   => 'laravel',
            'url'    => env('YG_DOCX_URL',              'https://docx.ygxone.com'),
            'health' => '/up',
            'icon'   => '📄',
            'order'  => 6,
        ],

        'yg-developer' => [
            'name'   => 'YG Developer',
            'path'   => env('ECOSYSTEM_PATH_DEVELOPER', $root . '/yg-developer'),
            'type'   => 'laravel',
            'url'    => env('YG_DEVELOPER_URL',         'https://developer.ygxone.com'),
            'health' => '/up',
            'icon'   => '⚡',
            'order'  => 7,
        ],

        'yg-chat' => [
            'name'   => 'YG Chat',
            'path'   => env('ECOSYSTEM_PATH_CHAT',      $root . '/yg-chat'),
            'type'   => 'laravel',
            'url'    => env('YG_CHAT_URL',              'https://chat.ygxone.com'),
            'health' => '/up',
            'icon'   => '💬',
            'order'  => 8,
        ],

        'yg-calendar' => [
            'name'   => 'YG Calendar',
            'path'   => env('ECOSYSTEM_PATH_CALENDAR',  $root . '/yg-calendar'),
            'type'   => 'laravel',
            'url'    => env('YG_CALENDAR_URL',          'https://calendar.ygxone.com'),
            'health' => '/up',
            'icon'   => '📅',
            'order'  => 9,
        ],

        'yg-contacts' => [
            'name'   => 'YG Contacts',
            'path'   => env('ECOSYSTEM_PATH_CONTACTS',  $root . '/yg-contacts'),
            'type'   => 'laravel',
            'url'    => env('YG_CONTACTS_URL',          'https://contacts.ygxone.com'),
            'health' => '/up',
            'icon'   => '👥',
            'order'  => 10,
        ],

        'yg-notes' => [
            'name'   => 'YG Notes',
            'path'   => env('ECOSYSTEM_PATH_NOTES',     $root . '/yg-notes'),
            'type'   => 'laravel',
            'url'    => env('YG_NOTES_URL',             'https://notes.ygxone.com'),
            'health' => '/up',
            'icon'   => '📝',
            'order'  => 11,
        ],

        'yg-meet' => [
            'name'   => 'YG Meet',
            'path'   => env('ECOSYSTEM_PATH_MEET',      $root . '/yg-meet'),
            'type'   => 'laravel',
            'url'    => env('YG_MEET_URL',              'https://meet.ygxone.com'),
            'health' => '/up',
            'icon'   => '🎥',
            'order'  => 12,
        ],

        'yg-pay' => [
            'name'   => 'YG Pay',
            'path'   => env('ECOSYSTEM_PATH_PAY',       $root . '/yg-pay'),
            'type'   => 'laravel',
            'url'    => env('YG_PAY_URL',               'https://pay.ygxone.com'),
            'health' => '/up',
            'icon'   => '💳',
            'order'  => 13,
        ],

        'yg-ai' => [
            'name'   => 'YG AI',
            'path'   => env('ECOSYSTEM_PATH_AI',        $root . '/ai'),
            'type'   => 'laravel',
            'url'    => env('YG_AI_URL',                'https://ai.ygxone.com'),
            'health' => '/up',
            'icon'   => '🤖',
            'order'  => 14,
        ],

        'yg-xcel' => [
            'name'   => 'YG Xcel',
            'path'   => env('ECOSYSTEM_PATH_XCEL',      $root . '/xcel'),
            'type'   => 'laravel',
            'url'    => env('YG_XCEL_URL',              'https://xcel.ygxone.com'),
            'health' => '/up',
            'icon'   => '📊',
            'order'  => 15,
        ],

        'yg-appstore' => [
            'name'   => 'YG AppStore',
            'path'   => env('ECOSYSTEM_PATH_APPSTORE',  $root . '/appstore'),
            'type'   => 'laravel',
            'url'    => env('YG_APPSTORE_URL',          'https://appstore.ygxone.com'),
            'health' => '/up',
            'icon'   => '🏪',
            'order'  => 16,
        ],

        'yg-collect' => [
            'name'   => 'YG Collect',
            'path'   => env('ECOSYSTEM_PATH_COLLECT',   $root . '/collect'),
            'type'   => 'laravel',
            'url'    => env('YG_COLLECT_URL',           'https://collect.ygxone.com'),
            'health' => '/up',
            'icon'   => '📋',
            'order'  => 17,
        ],

        'yg-console' => [
            'name'   => 'YG Console',
            'path'   => env('ECOSYSTEM_PATH_CONSOLE',   $root . '/console'),
            'type'   => 'laravel',
            'url'    => env('YG_CONSOLE_URL',           'https://console.ygxone.com'),
            'health' => '/up',
            'icon'   => '🎮',
            'order'  => 18,
        ],

        'yg-home' => [
            'name'   => 'YG Home',
            'path'   => env('ECOSYSTEM_PATH_HOME',      $root . '/home'),
            'type'   => 'laravel',
            'url'    => env('YG_HOME_URL',              'https://home.ygxone.com'),
            'health' => '/up',
            'icon'   => '🏠',
            'order'  => 19,
        ],

        'yg-support' => [
            'name'   => 'YG Support',
            'path'   => env('ECOSYSTEM_PATH_SUPPORT',   $root . '/support'),
            'type'   => 'laravel',
            'url'    => env('YG_SUPPORT_URL',           'https://support.ygxone.com'),
            'health' => '/up',
            'icon'   => '🛟',
            'order'  => 20,
        ],

    ],

];
