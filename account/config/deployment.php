<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Backup Directory
    |--------------------------------------------------------------------------
    |
    | Directory where deployment backups are stored for rollback purposes.
    |
    */
    'backup_directory' => env('DEPLOYMENT_BACKUP_DIRECTORY', storage_path('app/deployments')),

    /*
    |--------------------------------------------------------------------------
    | Automatic Rollback
    |--------------------------------------------------------------------------
    |
    | Enable automatic rollback when critical deployment-related failures occur.
    | WARNING: Only enable this in production after thorough testing.
    |
    */
    'auto_rollback_enabled' => env('DEPLOYMENT_AUTO_ROLLBACK_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Rollback Triggers
    |--------------------------------------------------------------------------
    |
    | Conditions that trigger automatic rollback:
    | - failure_threshold: Number of consecutive failures before rollback
    | - time_window: Time window (in minutes) to count failures
    |
    */
    'rollback_triggers' => [
        'failure_threshold' => 3,
        'time_window' => 10, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Git Repository Path
    |--------------------------------------------------------------------------
    |
    | Path to the git repository for code rollback operations.
    |
    */
    'git_repository_path' => base_path(),

    /*
    |--------------------------------------------------------------------------
    | Backup Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep deployment backups before cleanup.
    |
    */
    'backup_retention_days' => env('DEPLOYMENT_BACKUP_RETENTION_DAYS', 30),
];
