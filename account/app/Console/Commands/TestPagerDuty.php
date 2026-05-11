<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PagerDutyNotifier;

class TestPagerDuty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pagerduty:test 
                            {--resolve=DedupKey : Resolve an existing incident}
                            {--acknowledge=DedupKey : Acknowledge an existing incident}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PagerDuty integration and incident management';

    protected PagerDutyNotifier $pagerDuty;

    public function __construct(PagerDutyNotifier $pagerDuty)
    {
        parent::__construct();
        $this->pagerDuty = $pagerDuty;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->pagerDuty->isConfigured()) {
            $this->error('❌ PagerDuty is not configured. Please set PAGERDUTY_ROUTING_KEY in .env');
            return Command::FAILURE;
        }

        // Resolve incident
        if ($dedupKey = $this->option('resolve')) {
            $this->info("🔧 Resolving incident: {$dedupKey}");
            
            $result = $this->pagerDuty->resolveIncident($dedupKey, 'Resolved via CLI test');
            
            if ($result['success']) {
                $this->info('✅ Incident resolved successfully');
            } else {
                $this->error("❌ Failed to resolve: {$result['message']}");
                return Command::FAILURE;
            }
            
            return Command::SUCCESS;
        }

        // Acknowledge incident
        if ($dedupKey = $this->option('acknowledge')) {
            $this->info("👍 Acknowledging incident: {$dedupKey}");
            
            $result = $this->pagerDuty->acknowledgeIncident($dedupKey);
            
            if ($result['success']) {
                $this->info('✅ Incident acknowledged successfully');
            } else {
                $this->error("❌ Failed to acknowledge: {$result['message']}");
                return Command::FAILURE;
            }
            
            return Command::SUCCESS;
        }

        // Test integration (create new incident)
        $this->info('🧪 Testing PagerDuty integration...');
        $this->newLine();

        $result = $this->pagerDuty->testIntegration();

        if ($result['success']) {
            $this->info('✅ PagerDuty test successful!');
            $this->newLine();
            
            $this->info("Status: {$result['status']}");
            if (isset($result['dedup_key'])) {
                $this->info("Dedup Key: {$result['dedup_key']}");
                $this->newLine();
                $this->warn('💡 Use this dedup key to resolve or acknowledge the incident:');
                $this->line("   php artisan pagerduty:test --resolve={$result['dedup_key']}");
                $this->line("   php artisan pagerduty:test --acknowledge={$result['dedup_key']}");
            }
        } else {
            $this->error('❌ PagerDuty test failed!');
            $this->newLine();
            $this->error($result['message']);
            
            if (isset($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
