<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GrafanaExporter;

class ExportGrafanaMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grafana:export-metrics 
                            {--test : Test connection only}
                            {--dashboard-url : Display dashboard URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export cron job metrics to Grafana via InfluxDB for advanced analytics';

    protected GrafanaExporter $exporter;

    public function __construct(GrafanaExporter $exporter)
    {
        parent::__construct();
        $this->exporter = $exporter;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Test mode
        if ($this->option('test')) {
            $this->info('🔍 Testing Grafana/InfluxDB connection...');
            $result = $this->exporter->testConnection();
            
            if ($result['success']) {
                $this->info('✅ Connection successful!');
            } else {
                $this->error('❌ Connection failed: ' . $result['message']);
                return Command::FAILURE;
            }
            
            return Command::SUCCESS;
        }

        // Display dashboard URL
        if ($this->option('dashboard-url')) {
            $url = $this->exporter->getDashboardUrl();
            
            if ($url) {
                $this->info('📊 Grafana Dashboard URL:');
                $this->line($url);
            } else {
                $this->warn('⚠️  Dashboard URL not configured');
            }
            
            return Command::SUCCESS;
        }

        // Export metrics
        $this->info('📤 Exporting cron job metrics to Grafana...');
        $result = $this->exporter->exportMetrics();

        if ($result['success']) {
            $this->info("✅ Successfully exported {$result['exported_count']} job metrics");
            $this->line("📅 Timestamp: {$result['timestamp']}");
            
            $dashboardUrl = $this->exporter->getDashboardUrl();
            if ($dashboardUrl) {
                $this->newLine();
                $this->info('📊 View your metrics:');
                $this->line($dashboardUrl);
            }
        } else {
            $this->error('❌ Export failed: ' . $result['message']);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
