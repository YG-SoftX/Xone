<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ==========================================
// SCHEDULED TASKS FOR YG PLATFORM
// ==========================================

// Process pending events every minute (for real-time sync)
Schedule::command('events:process')->everyMinute();

// Clean up old events daily at midnight
Schedule::command('events:cleanup')->dailyAt('00:00');

// Aggregate usage statistics hourly
// Schedule::command('analytics:aggregate')->hourly();

// Empty trash older than 30 days (daily at 2 AM)
// Schedule::command('drive:empty-trash')->dailyAt('02:00');

// Generate monthly invoices (1st of each month at 3 AM)
// Schedule::command('billing:generate-invoices')->monthlyOn(1, '03:00');

// Send daily activity summaries (every day at 8 AM)
// Schedule::command('notifications:daily-summary')->dailyAt('08:00');

// ==========================================
// ISOLATED PAYMENT MODULE SECURITY TASKS
// ==========================================

// Run security scan every hour
Schedule::call(function () {
    $monitor = app(\App\Services\Payment\PaymentSecurityMonitor::class);
    $results = $monitor->runSecurityScan();
    
    // Log results
    Log::channel('payment_security')->info('Automated security scan completed', [
        'status' => $results['status'],
        'alerts_count' => count($results['alerts']),
    ]);
})->hourly()->name('isolated-payment-security-scan');

// Clean old security logs weekly (Sunday at 3 AM)
Schedule::call(function () {
    $retentionDays = config('isolated_payment_security.log_retention_days', 90);
    $logFile = storage_path('logs/payment_security.log');
    
    if (file_exists($logFile)) {
        $cutoffDate = now()->subDays($retentionDays);
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        $filteredLines = [];
        
        foreach ($lines as $line) {
            // Keep lines newer than cutoff date or non-dated lines
            if (!preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                $filteredLines[] = $line;
            } elseif (strtotime($matches[1]) >= $cutoffDate->timestamp) {
                $filteredLines[] = $line;
            }
        }
        
        file_put_contents($logFile, implode("\n", $filteredLines));
        
        Log::info('Old payment security logs cleaned', [
            'retention_days' => $retentionDays,
            'lines_removed' => count($lines) - count($filteredLines),
        ]);
    }
})->weeklyOn(0, '03:00')->name('isolated-payment-security-log-cleanup');

// Anonymize old transaction data monthly (1st of month at 4 AM)
Schedule::call(function () {
    if (config('isolated_payment_security.anonymize_old_logs', true)) {
        $gdprRetention = config('isolated_payment_security.gdpr_retention_days', 365);
        $cutoffDate = now()->subDays($gdprRetention);
        
        // Anonymize IP addresses in old transactions
        DB::table('pay_transactions')
            ->where('created_at', '<', $cutoffDate)
            ->whereNotNull('ip_address')
            ->update([
                'ip_address' => DB::raw("CONCAT(SUBSTRING_INDEX(ip_address, '.', 2), '.***.***)"),
                'user_agent' => DB::raw("'Anonymized'"),
            ]);
        
        Log::info('Old transaction data anonymized for GDPR compliance', [
            'retention_days' => $gdprRetention,
            'records_anonymized' => DB::table('pay_transactions')
                ->where('created_at', '<', $cutoffDate)
                ->count(),
        ]);
    }
})->monthlyOn(1, '04:00')->name('isolated-payment-gdpr-anonymize');
