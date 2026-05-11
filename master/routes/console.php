<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\HealthCheckJob;
use App\Jobs\AggregateMetricsJob;

// Health Check - Every minute for real-time monitoring
Schedule::job(new HealthCheckJob())->everyMinute()->withoutOverlapping();

// Aggregate Metrics - Hourly for dashboard statistics
Schedule::job(new AggregateMetricsJob('hourly'))->hourlyAt(0)->withoutOverlapping();
Schedule::job(new AggregateMetricsJob('daily'))->dailyAt('01:00')->withoutOverlapping();

// Database Backup - Daily at 2 AM
// Schedule::command('db:backup')->dailyAt('02:00');

// Cache Optimization - Weekly
Schedule::command('filament:optimize')->weeklyOn(1, '03:00');

// Log Rotation - Daily
Schedule::command('log:clear')->daily();

// Queue Monitoring - Every 5 minutes
// Schedule::command('queue:monitor')->everyFiveMinutes();
