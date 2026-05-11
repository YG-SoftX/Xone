<?php

namespace Yuga\Core;

/**
 * Yuga Financial Analyst
 * Predictive intelligence for YG Pay and ecosystem economics.
 */
class FinancialAnalyst
{
    private $config;
    private $dataDir;

    public function __construct(array $config, string $dataDir)
    {
        $this->config = $config;
        $this->dataDir = $dataDir;
    }

    /**
     * Analyze anonymized financial datasets
     * Returns: [insights => array, metrics => array, anomalies => array]
     */
    public function analyze(array $data): array
    {
        $metrics = [
            'total_volume' => 0.0,
            'mrr' => 0.0,
            'active_subscriptions' => 0,
            'churn_rate' => 0.0,
            'avg_transaction' => 0.0
        ];

        $transactions = $data['transactions'] ?? [];
        $subscriptions = $data['subscriptions'] ?? [];
        
        // 1. Transaction Metrics
        if (!empty($transactions)) {
            $metrics['total_volume'] = array_sum(array_column($transactions, 'amount'));
            $metrics['avg_transaction'] = $metrics['total_volume'] / count($transactions);
        }

        // 2. Subscription & MRR Analysis
        $activeCount = 0;
        $cancelledCount = 0;
        foreach ($subscriptions as $sub) {
            if ($sub['status'] === 'active') {
                $activeCount++;
                // Simple MRR calculation (assumes monthly for now)
                $metrics['mrr'] += (float)$sub['amount'];
            } elseif ($sub['status'] === 'cancelled' || $sub['status'] === 'expired') {
                $cancelledCount++;
            }
        }
        $metrics['active_subscriptions'] = $activeCount;
        $metrics['churn_rate'] = ($activeCount + $cancelledCount) > 0 
            ? ($cancelledCount / ($activeCount + $cancelledCount)) * 100 
            : 0.0;

        // 3. Predictive Insights
        $insights = $this->generateInsights($metrics);
        
        // 4. Anomaly Detection
        $anomalies = $this->detectAnomalies($transactions);

        return [
            'metrics' => $metrics,
            'insights' => $insights,
            'anomalies' => $anomalies,
            'timestamp' => time()
        ];
    }

    private function generateInsights(array $metrics): array
    {
        $insights = [];
        
        if ($metrics['churn_rate'] > 15) {
            $insights[] = "High Churn Alert: The ecosystem is losing subscribers at a rate of " . round($metrics['churn_rate'], 1) . "%. Consider a retention campaign.";
        } else {
            $insights[] = "Stable Retention: Churn is currently at " . round($metrics['churn_rate'], 1) . "%, which is within a healthy range.";
        }

        $projectedYearly = $metrics['mrr'] * 12;
        $insights[] = "Projected ARR: Based on current MRR, the ecosystem is on track for $" . number_format($projectedYearly, 2) . " in annual recurring revenue.";

        return $insights;
    }

    private function detectAnomalies(array $transactions): array
    {
        $anomalies = [];
        if (empty($transactions)) return $anomalies;

        $avg = array_sum(array_column($transactions, 'amount')) / count($transactions);
        $threshold = $avg * 5; // Simple threshold: 5x the average

        foreach ($transactions as $tx) {
            if ($tx['amount'] > $threshold) {
                $anomalies[] = [
                    'id' => $tx['transaction_id'] ?? 'unknown',
                    'type' => 'High Volume Alert',
                    'message' => "Transaction of $" . number_format($tx['amount'], 2) . " is significantly above the average pattern."
                ];
            }
        }

        return $anomalies;
    }
}
