<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CronJob;

class CronJobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cronJobs = [
            [
                'name' => 'laravel-scheduler',
                'command' => '/usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan schedule:run >> /dev/null 2>&1',
                'schedule' => '* * * * *',
                'description' => 'Laravel Task Scheduler - Runs all scheduled tasks (cleanup, analytics, invoices, etc.)',
                'is_enabled' => true,
                'is_system' => true,
                'status' => 'pending',
                'metadata' => json_encode([
                    'category' => 'system',
                    'priority' => 'critical',
                    'timeout' => 60,
                ]),
            ],
            [
                'name' => 'queue-worker',
                'command' => '/usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan queue:work --stop-when-empty >> /dev/null 2>&1',
                'schedule' => '*/5 * * * *',
                'description' => 'Queue Worker - Processes background jobs every 5 minutes (only if using database queue)',
                'is_enabled' => false, // Disabled by default (sync mode recommended for cPanel)
                'is_system' => true,
                'status' => 'pending',
                'metadata' => json_encode([
                    'category' => 'system',
                    'priority' => 'high',
                    'timeout' => 300,
                    'note' => 'Only enable if QUEUE_CONNECTION=database',
                ]),
            ],
            [
                'name' => 'log-rotation',
                'command' => 'find /home/YOUR_USERNAME/yg-account-core/storage/logs -name "*.log" -mtime +7 -delete',
                'schedule' => '0 0 * * 0',
                'description' => 'Log Rotation - Deletes log files older than 7 days every Sunday at midnight',
                'is_enabled' => true,
                'is_system' => false,
                'status' => 'pending',
                'metadata' => json_encode([
                    'category' => 'maintenance',
                    'priority' => 'medium',
                ]),
            ],
            [
                'name' => 'session-cleanup',
                'command' => '/usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan session:table >> /dev/null 2>&1',
                'schedule' => '0 2 * * *',
                'description' => 'Session Cleanup - Removes expired database sessions daily at 2 AM',
                'is_enabled' => true,
                'is_system' => true,
                'status' => 'pending',
                'metadata' => json_encode([
                    'category' => 'maintenance',
                    'priority' => 'medium',
                ]),
            ],
            [
                'name' => 'database-backup',
                'command' => 'mysqldump -u YOUR_DB_USER -p\'YOUR_DB_PASS\' YOUR_DB_NAME | gzip > /home/YOUR_USERNAME/backups/db_$(date +\%Y\%m\%d).sql.gz',
                'schedule' => '0 3 * * *',
                'description' => 'Database Backup - Creates compressed backup daily at 3 AM',
                'is_enabled' => false, // User must configure DB credentials first
                'is_system' => false,
                'status' => 'pending',
                'metadata' => json_encode([
                    'category' => 'backup',
                    'priority' => 'high',
                    'requires_setup' => true,
                ]),
            ],
            [
                'name' => 'analytics-aggregation',
                'command' => '/usr/bin/php /home/YOUR_USERNAME/yg-account-core/artisan analytics:aggregate >> /dev/null 2>&1',
                'schedule' => '0 */6 * * *',
                'description' => 'Analytics Aggregation - Compiles usage statistics every 6 hours',
                'is_enabled' => true,
                'is_system' => true,
                'status' => 'pending',
                'metadata' => json_encode([
                    'category' => 'analytics',
                    'priority' => 'low',
                ]),
            ],
        ];

        foreach ($cronJobs as $jobData) {
            CronJob::updateOrCreate(
                ['name' => $jobData['name']],
                $jobData
            );
        }

        $this->command->info('✅ Default cron jobs seeded successfully!');
        $this->command->warn('⚠️  Remember to replace YOUR_USERNAME, YOUR_DB_USER, etc. with actual values in cPanel.');
    }
}
