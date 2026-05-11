<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes - Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Send heartbeat to YG Master every 5 minutes
Schedule::command('yg-master:heartbeat')->everyFiveMinutes();

// Report metrics to YG Master every hour
Schedule::command('yg-master:report-metrics')->hourly();

// Clear expired API keys daily at midnight
Schedule::command('api-keys:cleanup')->dailyAt('00:00');

// Generate monthly invoices on the 1st of each month
Schedule::command('billing:generate-invoices')->monthlyOn(1, '00:00');

// Clean up old webhook delivery logs weekly
Schedule::command('webhooks:cleanup')->weekly();

// Archive inactive projects monthly
Schedule::command('projects:archive-inactive')->monthly();
