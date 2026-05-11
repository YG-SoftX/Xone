<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CronJob;

class ExportCpanelCronJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cpanel:export-cron-jobs 
                            {--username= : cPanel username}
                            {--output= : Output file path (default: storage/app/cpanel-cron-jobs.txt)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all enabled cron jobs as cPanel-compatible commands';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->option('username');
        $outputPath = $this->option('output') ?? storage_path('app/cpanel-cron-jobs.txt');

        if (!$username) {
            $this->error('❌ cPanel username is required!');
            $this->info('Usage: php artisan cpanel:export-cron-jobs --username=your_cpanel_username');
            return Command::FAILURE;
        }

        $cronJobs = CronJob::where('is_enabled', true)->get();

        if ($cronJobs->isEmpty()) {
            $this->warn('⚠️  No enabled cron jobs found.');
            return Command::SUCCESS;
        }

        $content = $this->generateCpanelContent($cronJobs, $username);

        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($outputPath, $content);

        $this->info("✅ Cron jobs exported successfully!");
        $this->line("📁 File saved to: {$outputPath}");
        $this->newLine();
        
        $this->info("📋 Next Steps:");
        $this->line("1. Login to cPanel");
        $this->line("2. Navigate to: Advanced → Cron Jobs");
        $this->line("3. Copy commands from the generated file");
        $this->line("4. Add each command with matching schedule");
        $this->newLine();

        // Display summary table
        $this->table(
            ['Job Name', 'Schedule', 'Status'],
            $cronJobs->map(fn($job) => [
                $job->name,
                $job->schedule,
                $job->is_system ? '🔒 System' : '✏️ User',
            ])->toArray()
        );

        return Command::SUCCESS;
    }

    /**
     * Generate cPanel-formatted content
     */
    protected function generateCpanelContent($cronJobs, string $username): string
    {
        $lines = [];
        $lines[] = "# ╔══════════════════════════════════════════════════════╗";
        $lines[] = "# ║  YG Account - cPanel Cron Jobs                     ║";
        $lines[] = "# ║  Generated: " . now()->format('Y-m-d H:i:s');
        $lines[] = "# ║  Username: {$username}";
        $lines[] = "# ╚══════════════════════════════════════════════════════╝";
        $lines[] = "";
        $lines[] = "# IMPORTANT: Replace YOUR_USERNAME with: {$username}";
        $lines[] = "";

        foreach ($cronJobs as $index => $job) {
            $lines[] = "# ── Job #" . ($index + 1) . ": {$job->name} ────────────────────────────────";
            $lines[] = "# Description: {$job->description}";
            $lines[] = "# Schedule: {$job->schedule}";
            
            // Replace placeholder with actual username
            $command = str_replace('YOUR_USERNAME', $username, $job->command);
            $lines[] = $command;
            $lines[] = "";
        }

        $lines[] = "# ═══════════════════════════════════════════════════════════════";
        $lines[] = "# End of cron jobs configuration";
        $lines[] = "# Total jobs: " . $cronJobs->count();
        $lines[] = "# ═══════════════════════════════════════════════════════════════";

        return implode("\n", $lines);
    }
}
