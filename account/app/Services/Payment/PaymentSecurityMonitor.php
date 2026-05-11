<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

/**
 * PaymentSecurityMonitor Service
 * 
 * Continuously monitors the isolated payment module for security threats,
 * anomalies, and potential breaches. Provides real-time alerts and automated responses.
 */
class PaymentSecurityMonitor
{
    private $alertThresholds = [
        'failed_transactions_per_hour' => 50,
        'unique_ips_per_hour' => 200,
        'high_value_transactions_per_hour' => 10,
        'suspicious_patterns_detected' => 5,
    ];

    /**
     * Run comprehensive security scan
     */
    public function runSecurityScan(): array
    {
        $scanResults = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'status' => 'PASS',
            'checks' => [],
            'alerts' => [],
        ];

        // Check 1: File integrity verification
        $integrityCheck = $this->checkFileIntegrity();
        $scanResults['checks']['file_integrity'] = $integrityCheck;
        
        if (!$integrityCheck['passed']) {
            $scanResults['status'] = 'FAIL';
            $scanResults['alerts'][] = [
                'severity' => 'CRITICAL',
                'message' => 'File integrity violation detected',
                'details' => $integrityCheck['violations'],
            ];
            $this->sendAlert('CRITICAL', 'File Integrity Violation', $integrityCheck['violations']);
        }

        // Check 2: Analyze recent transaction patterns
        $patternAnalysis = $this->analyzeTransactionPatterns();
        $scanResults['checks']['transaction_patterns'] = $patternAnalysis;
        
        if ($patternAnalysis['anomalous']) {
            $scanResults['status'] = 'WARNING';
            $scanResults['alerts'][] = [
                'severity' => 'HIGH',
                'message' => 'Anomalous transaction patterns detected',
                'details' => $patternAnalysis['anomalies'],
            ];
            $this->sendAlert('HIGH', 'Anomalous Transaction Patterns', $patternAnalysis['anomalies']);
        }

        // Check 3: Monitor error rates
        $errorRateCheck = $this->checkErrorRates();
        $scanResults['checks']['error_rates'] = $errorRateCheck;
        
        if ($errorRateCheck['elevated']) {
            $scanResults['status'] = 'WARNING';
            $scanResults['alerts'][] = [
                'severity' => 'MEDIUM',
                'message' => 'Elevated error rate detected',
                'details' => $errorRateCheck['details'],
            ];
        }

        // Check 4: IP reputation check
        $ipReputationCheck = $this->checkIpReputation();
        $scanResults['checks']['ip_reputation'] = $ipReputationCheck;
        
        if (!empty($ipReputationCheck['suspicious_ips'])) {
            $scanResults['alerts'][] = [
                'severity' => 'MEDIUM',
                'message' => 'Suspicious IP addresses detected',
                'details' => $ipReputationCheck['suspicious_ips'],
            ];
        }

        // Check 5: Rate limit violations
        $rateLimitCheck = $this->checkRateLimitViolations();
        $scanResults['checks']['rate_limits'] = $rateLimitCheck;
        
        if ($rateLimitCheck['violations'] > 0) {
            $scanResults['alerts'][] = [
                'severity' => 'LOW',
                'message' => "Rate limit violations: {$rateLimitCheck['violations']}",
                'details' => $rateLimitCheck['details'],
            ];
        }

        // Check 6: SSL/TLS configuration
        $sslCheck = $this->checkSslConfiguration();
        $scanResults['checks']['ssl_configuration'] = $sslCheck;
        
        if (!$sslCheck['valid']) {
            $scanResults['status'] = 'FAIL';
            $scanResults['alerts'][] = [
                'severity' => 'HIGH',
                'message' => 'SSL/TLS configuration issues',
                'details' => $sslCheck['issues'],
            ];
            $this->sendAlert('HIGH', 'SSL/TLS Configuration Issues', $sslCheck['issues']);
        }

        // Store scan results
        cache()->put('payment_security_scan_' . time(), $scanResults, 3600);
        
        // Log scan completion
        Log::channel('payment_security')->info('Security scan completed', [
            'status' => $scanResults['status'],
            'total_alerts' => count($scanResults['alerts']),
            'timestamp' => $scanResults['timestamp'],
        ]);

        return $scanResults;
    }

    /**
     * Check file integrity of isolated module
     */
    private function checkFileIntegrity(): array
    {
        $modulePath = config('isolated_payment_security.isolated_module_path');
        $monitoredFiles = config('isolated_payment_security.monitored_files', []);
        
        $violations = [];
        $passed = true;

        foreach ($monitoredFiles as $file) {
            $fullPath = $modulePath . '/' . $file;
            
            if (!file_exists($fullPath)) {
                $violations[] = [
                    'file' => $file,
                    'issue' => 'File missing',
                    'severity' => 'CRITICAL',
                ];
                $passed = false;
                continue;
            }

            // Check file permissions
            $perms = fileperms($fullPath);
            $expectedPerms = 0644; // Should not be world-writable
            
            if (($perms & 0x1FF) > $expectedPerms) {
                $violations[] = [
                    'file' => $file,
                    'issue' => 'Insecure file permissions',
                    'current' => decoct($perms & 0x1FF),
                    'expected' => decoct($expectedPerms),
                    'severity' => 'HIGH',
                ];
                $passed = false;
            }

            // Check for recent modifications (within last hour might be suspicious)
            $lastModified = filemtime($fullPath);
            $oneHourAgo = time() - 3600;
            
            if ($lastModified > $oneHourAgo) {
                // Check if this is during maintenance window
                if (!$this->isMaintenanceWindow()) {
                    $violations[] = [
                        'file' => $file,
                        'issue' => 'Unexpected file modification',
                        'modified_at' => date('Y-m-d H:i:s', $lastModified),
                        'severity' => 'MEDIUM',
                    ];
                }
            }

            // Calculate and store hash for future comparison
            $hash = hash_file('sha256', $fullPath);
            $cacheKey = "file_hash_" . md5($file);
            $previousHash = cache()->get($cacheKey);
            
            if ($previousHash && $previousHash !== $hash) {
                $violations[] = [
                    'file' => $file,
                    'issue' => 'File content changed',
                    'previous_hash' => substr($previousHash, 0, 16) . '...',
                    'current_hash' => substr($hash, 0, 16) . '...',
                    'severity' => 'CRITICAL',
                ];
                $passed = false;
            }
            
            cache()->put($cacheKey, $hash, 86400); // Store for 24 hours
        }

        return [
            'passed' => $passed,
            'violations' => $violations,
            'files_checked' => count($monitoredFiles),
        ];
    }

    /**
     * Analyze transaction patterns for anomalies
     */
    private function analyzeTransactionPatterns(): array
    {
        $oneHourAgo = Carbon::now()->subHour();
        
        // Get transaction statistics
        $totalTransactions = DB::table('pay_transactions')
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        $failedTransactions = DB::table('pay_transactions')
            ->where('created_at', '>=', $oneHourAgo)
            ->where('status', 'failed')
            ->count();

        $uniqueIps = DB::table('pay_transactions')
            ->where('created_at', '>=', $oneHourAgo)
            ->distinct('ip_address')
            ->count('ip_address');

        $highValueTransactions = DB::table('pay_transactions')
            ->where('created_at', '>=', $oneHourAgo)
            ->where('amount', '>', 10000)
            ->count();

        $anomalies = [];
        $anomalous = false;

        // Check for unusual transaction volume
        if ($totalTransactions > $this->alertThresholds['failed_transactions_per_hour'] * 2) {
            $anomalies[] = [
                'type' => 'high_volume',
                'count' => $totalTransactions,
                'threshold' => $this->alertThresholds['failed_transactions_per_hour'] * 2,
            ];
            $anomalous = true;
        }

        // Check for high failure rate
        if ($totalTransactions > 0) {
            $failureRate = ($failedTransactions / $totalTransactions) * 100;
            if ($failureRate > 50) {
                $anomalies[] = [
                    'type' => 'high_failure_rate',
                    'rate' => round($failureRate, 2) . '%',
                    'failed' => $failedTransactions,
                    'total' => $totalTransactions,
                ];
                $anomalous = true;
            }
        }

        // Check for too many unique IPs (potential DDoS or fraud)
        if ($uniqueIps > $this->alertThresholds['unique_ips_per_hour']) {
            $anomalies[] = [
                'type' => 'excessive_unique_ips',
                'count' => $uniqueIps,
                'threshold' => $this->alertThresholds['unique_ips_per_hour'],
            ];
            $anomalous = true;
        }

        // Check for unusual high-value transactions
        if ($highValueTransactions > $this->alertThresholds['high_value_transactions_per_hour']) {
            $anomalies[] = [
                'type' => 'excessive_high_value',
                'count' => $highValueTransactions,
                'threshold' => $this->alertThresholds['high_value_transactions_per_hour'],
            ];
            $anomalous = true;
        }

        return [
            'anomalous' => $anomalous,
            'anomalies' => $anomalies,
            'statistics' => [
                'total_transactions' => $totalTransactions,
                'failed_transactions' => $failedTransactions,
                'unique_ips' => $uniqueIps,
                'high_value_transactions' => $highValueTransactions,
            ],
        ];
    }

    /**
     * Check error rates
     */
    private function checkErrorRates(): array
    {
        $oneHourAgo = Carbon::now()->subHour();
        
        $totalRequests = cache()->get('payment_total_requests_1h', 0);
        $errorCount = cache()->get('payment_error_count_1h', 0);
        
        $errorRate = $totalRequests > 0 ? ($errorCount / $totalRequests) * 100 : 0;
        
        return [
            'elevated' => $errorRate > 20, // More than 20% error rate
            'error_rate' => round($errorRate, 2) . '%',
            'total_requests' => $totalRequests,
            'errors' => $errorCount,
            'details' => [
                'error_rate_percentage' => $errorRate,
                'threshold' => 20,
            ],
        ];
    }

    /**
     * Check IP reputation
     */
    private function checkIpReputation(): array
    {
        $oneHourAgo = Carbon::now()->subHour();
        
        // Get IPs with multiple failed attempts
        $suspiciousIps = DB::table('pay_transactions')
            ->select('ip_address', DB::raw('COUNT(*) as fail_count'))
            ->where('created_at', '>=', $oneHourAgo)
            ->where('status', 'failed')
            ->groupBy('ip_address')
            ->having('fail_count', '>=', 5)
            ->get();

        $suspiciousIpList = [];
        foreach ($suspiciousIps as $ip) {
            $suspiciousIpList[] = [
                'ip' => $ip->ip_address,
                'failed_attempts' => $ip->fail_count,
            ];
        }

        return [
            'suspicious_ips' => $suspiciousIpList,
            'total_suspicious' => count($suspiciousIpList),
        ];
    }

    /**
     * Check rate limit violations
     */
    private function checkRateLimitViolations(): array
    {
        // This would check your rate limiting logs
        // Implementation depends on your rate limiting strategy
        
        return [
            'violations' => 0,
            'details' => [],
        ];
    }

    /**
     * Check SSL/TLS configuration
     */
    private function checkSslConfiguration(): array
    {
        $enforceHttps = config('isolated_payment_security.enforce_https', true);
        $verifySsl = config('isolated_payment_security.verify_ssl', true);
        
        $issues = [];
        $valid = true;

        if (!$enforceHttps) {
            $issues[] = 'HTTPS enforcement is disabled';
            $valid = false;
        }

        if (!$verifySsl) {
            $issues[] = 'SSL verification is disabled';
            $valid = false;
        }

        // Check current request protocol
        if (request() && !request()->secure() && app()->environment('production')) {
            $issues[] = 'Current request is not using HTTPS in production';
            $valid = false;
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'https_enforced' => $enforceHttps,
            'ssl_verified' => $verifySsl,
        ];
    }

    /**
     * Check if currently in maintenance window
     */
    private function isMaintenanceWindow(): bool
    {
        // Define maintenance windows (e.g., 2 AM - 4 AM local time)
        $currentHour = Carbon::now()->hour;
        return $currentHour >= 2 && $currentHour < 4;
    }

    /**
     * Send security alert
     */
    private function sendAlert(string $severity, string $title, array $details): void
    {
        if (!config('isolated_payment_security.alert_on_suspicious', true)) {
            return;
        }

        $alertEmails = config('isolated_payment_security.alert_emails', ['admin@ygxone.com']);
        
        $message = "Security Alert: {$title}\n\n";
        $message .= "Severity: {$severity}\n";
        $message .= "Timestamp: " . Carbon::now()->toIso8601String() . "\n\n";
        $message .= "Details:\n";
        $message .= json_encode($details, JSON_PRETTY_PRINT) . "\n";

        // Log the alert
        Log::channel('payment_security')->alert("{$severity}: {$title}", $details);

        // Send email alerts for HIGH and CRITICAL
        if (in_array($severity, ['HIGH', 'CRITICAL'])) {
            try {
                Mail::raw($message, function ($mail) use ($alertEmails, $title, $severity) {
                    $mail->to($alertEmails)
                        ->subject("[{$severity}] Payment Security Alert: {$title}");
                });
            } catch (\Exception $e) {
                Log::error('Failed to send security alert email', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // For CRITICAL alerts, also notify via additional channels (SMS, Slack, etc.)
        if ($severity === 'CRITICAL') {
            $this->sendCriticalAlert($title, $details);
        }
    }

    /**
     * Send critical alert through multiple channels
     */
    private function sendCriticalAlert(string $title, array $details): void
    {
        // Integration with SMS gateway, Slack, PagerDuty, etc.
        // This is a placeholder for multi-channel alerting
        
        Log::channel('payment_security')->critical('CRITICAL ALERT dispatched', [
            'title' => $title,
            'details' => $details,
        ]);
    }

    /**
     * Get security dashboard data
     */
    public function getSecurityDashboard(): array
    {
        $last24Hours = Carbon::now()->subDay();
        
        return [
            'summary' => [
                'total_transactions_24h' => DB::table('pay_transactions')
                    ->where('created_at', '>=', $last24Hours)
                    ->count(),
                'failed_transactions_24h' => DB::table('pay_transactions')
                    ->where('created_at', '>=', $last24Hours)
                    ->where('status', 'failed')
                    ->count(),
                'total_amount_24h' => DB::table('pay_transactions')
                    ->where('created_at', '>=', $last24Hours)
                    ->where('status', 'completed')
                    ->sum('amount'),
                'unique_users_24h' => DB::table('pay_transactions')
                    ->where('created_at', '>=', $last24Hours)
                    ->distinct('user_id')
                    ->count('user_id'),
            ],
            'security_status' => $this->getCurrentSecurityStatus(),
            'recent_alerts' => $this->getRecentAlerts(10),
            'top_suspicious_ips' => $this->getTopSuspiciousIps(10),
        ];
    }

    /**
     * Get current security status
     */
    private function getCurrentSecurityStatus(): array
    {
        $lastScan = cache()->get('payment_security_scan_' . time());
        
        if (!$lastScan) {
            // Run a quick scan
            $lastScan = $this->runSecurityScan();
        }

        return [
            'overall_status' => $lastScan['status'],
            'last_scan' => $lastScan['timestamp'],
            'active_alerts' => count($lastScan['alerts']),
            'threat_level' => $this->calculateThreatLevel($lastScan),
        ];
    }

    /**
     * Calculate threat level based on scan results
     */
    private function calculateThreatLevel(array $scanResults): string
    {
        $criticalCount = 0;
        $highCount = 0;

        foreach ($scanResults['alerts'] as $alert) {
            if ($alert['severity'] === 'CRITICAL') {
                $criticalCount++;
            } elseif ($alert['severity'] === 'HIGH') {
                $highCount++;
            }
        }

        if ($criticalCount > 0) {
            return 'CRITICAL';
        } elseif ($highCount > 2) {
            return 'HIGH';
        } elseif ($highCount > 0) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Get recent security alerts
     */
    private function getRecentAlerts(int $limit = 10): array
    {
        // This would query your security alert logs
        // Placeholder implementation
        return [];
    }

    /**
     * Get top suspicious IPs
     */
    private function getTopSuspiciousIps(int $limit = 10): array
    {
        $oneDayAgo = Carbon::now()->subDay();
        
        return DB::table('pay_transactions')
            ->select('ip_address', DB::raw('COUNT(*) as attempt_count'))
            ->where('created_at', '>=', $oneDayAgo)
            ->where('status', 'failed')
            ->groupBy('ip_address')
            ->orderBy('attempt_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Emergency disable all isolated payments
     */
    public function emergencyDisable(): void
    {
        config(['isolated_payment_security.emergency_disable' => true]);
        
        Log::channel('payment_security')->emergency('EMERGENCY DISABLE activated', [
            'triggered_by' => auth()->id() ?? 'system',
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        $this->sendAlert('CRITICAL', 'Emergency Disable Activated', [
            'message' => 'All isolated payment processing has been disabled',
            'triggered_by' => auth()->id() ?? 'system',
        ]);
    }

    /**
     * Re-enable after emergency
     */
    public function reEnableAfterEmergency(): void
    {
        config(['isolated_payment_security.emergency_disable' => false]);
        
        Log::channel('payment_security')->info('Emergency disable deactivated', [
            'triggered_by' => auth()->id() ?? 'system',
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }
}
