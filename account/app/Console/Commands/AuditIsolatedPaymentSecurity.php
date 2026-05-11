<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Payment\PaymentSecurityMonitor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuditIsolatedPaymentSecurity extends Command
{
    protected $signature = 'payment:security-audit
                            {--full : Run full comprehensive audit}
                            {--quick : Run quick security check only}
                            {--report : Generate detailed report}';

    protected $description = 'Run comprehensive security audit on isolated payment module';

    private $monitor;

    public function __construct(PaymentSecurityMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function handle()
    {
        $this->info('🔒 Starting Isolated Payment Module Security Audit...');
        $this->newLine();

        $startTime = microtime(true);

        if ($this->option('quick')) {
            $this->runQuickAudit();
        } else {
            $this->runFullAudit();
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $this->newLine();
        $this->info("✅ Audit completed in {$executionTime}ms");

        if ($this->option('report')) {
            $this->generateReport();
        }

        return self::SUCCESS;
    }

    private function runQuickAudit(): void
    {
        $this->info('Running quick security check...');
        $this->checkModuleStatus();
        $this->checkFileIntegrity();
        $this->checkConfiguration();
    }

    private function runFullAudit(): void
    {
        $this->info('Running comprehensive security audit...');
        
        $this->section('Module Configuration');
        $this->checkModuleStatus();
        $this->checkConfiguration();

        $this->section('File System Security');
        $this->checkFileIntegrity();
        $this->checkFilePermissions();

        $this->section('Network Security');
        $this->checkSslConfiguration();

        $this->section('Transaction Security');
        $this->analyzeTransactionPatterns();

        $this->section('Automated Security Scan');
        $scanResults = $this->monitor->runSecurityScan();
        $this->displayScanResults($scanResults);
    }

    private function checkModuleStatus(): void
    {
        $enabled = config('isolated_payment_security.enabled', false);
        $emergencyDisable = config('isolated_payment_security.emergency_disable', false);
        
        if ($emergencyDisable) {
            $this->error('❌ EMERGENCY DISABLE ACTIVE');
        } elseif ($enabled) {
            $this->info('✅ Module Enabled');
        } else {
            $this->warn('⚠️ Module Disabled');
        }
        $this->newLine();
    }

    private function checkFileIntegrity(): void
    {
        $modulePath = config('isolated_payment_security.isolated_module_path');
        $monitoredFiles = config('isolated_payment_security.monitored_files', []);
        
        foreach ($monitoredFiles as $file) {
            $fullPath = $modulePath . '/' . $file;
            if (file_exists($fullPath)) {
                $this->info("✅ {$file} exists");
            } else {
                $this->error("❌ {$file} missing");
            }
        }
        $this->newLine();
    }

    private function checkFilePermissions(): void
    {
        $modulePath = config('isolated_payment_security.isolated_module_path');
        if (is_dir($modulePath)) {
            $perms = fileperms($modulePath);
            $this->line("Directory permissions: " . decoct($perms & 0x1FF));
        }
        $this->newLine();
    }

    private function checkSslConfiguration(): void
    {
        $enforceHttps = config('isolated_payment_security.enforce_https', true);
        $verifySsl = config('isolated_payment_security.verify_ssl', true);
        
        $this->line("HTTPS Enforcement: " . ($enforceHttps ? '✅ Enabled' : '❌ Disabled'));
        $this->line("SSL Verification: " . ($verifySsl ? '✅ Enabled' : '❌ Disabled'));
        $this->newLine();
    }

    private function analyzeTransactionPatterns(): void
    {
        $oneHourAgo = now()->subHour();
        
        $totalTransactions = DB::table('pay_transactions')
            ->where('created_at', '>=', $oneHourAgo)
            ->count();
        
        $failedTransactions = DB::table('pay_transactions')
            ->where('created_at', '>=', $oneHourAgo)
            ->where('status', 'failed')
            ->count();
        
        $this->line("Transactions (last hour): {$totalTransactions}");
        $this->line("Failed: {$failedTransactions}");
        $this->newLine();
    }

    private function checkConfiguration(): void
    {
        $this->info('Configuration Summary:');
        $this->line("Security Level: " . config('isolated_payment_security.security_level', 'STRICT'));
        $this->line("Rate Limit: " . config('isolated_payment_security.payment_rate_limit', 100) . '/hour');
        $this->line("Logging: " . (config('isolated_payment_security.enable_security_logging') ? '✅ Enabled' : '❌ Disabled'));
        $this->newLine();
    }

    private function displayScanResults(array $results): void
    {
        $this->line("Scan Status: {$results['status']}");
        $this->line("Alerts: " . count($results['alerts']));
        
        foreach ($results['alerts'] as $alert) {
            $this->warn("[{$alert['severity']}] {$alert['message']}");
        }
        $this->newLine();
    }

    private function generateReport(): void
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'scan_results' => $this->monitor->runSecurityScan(),
        ];
        
        $reportPath = storage_path('app/audits/payment_security_' . date('Y-m-d_H-i-s') . '.json');
        
        if (!is_dir(dirname($reportPath))) {
            mkdir(dirname($reportPath), 0755, true);
        }
        
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("Report saved to: {$reportPath}");
    }
}
