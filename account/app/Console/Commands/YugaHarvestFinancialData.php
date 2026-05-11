<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class YugaHarvestFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'yuga:harvest-financial {--days=30 : Number of days to harvest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Harvest anonymized YG Pay data for Yuga 1.0 Financial Intelligence';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Financial Intelligence Harvest...');
        $days = $this->option('days');
        $dateLimit = now()->subDays($days);

        // 1. Harvest Anonymized Transactions
        $transactions = DB::table('pay_transactions')
            ->select('type', 'amount', 'currency', 'status', 'created_at')
            ->where('created_at', '>=', $dateLimit)
            ->get();

        // 2. Harvest Anonymized Subscriptions (Churn analysis)
        $subscriptions = DB::table('pay_subscriptions')
            ->select('status', 'amount', 'currency', 'interval', 'created_at', 'cancelled_at')
            ->where('created_at', '>=', $dateLimit)
            ->get();

        $dataset = [
            'metadata' => [
                'harvest_time' => now()->toIso8601String(),
                'source' => 'YG Pay',
                'days_covered' => $days,
            ],
            'transactions' => $transactions,
            'subscriptions' => $subscriptions
        ];

        $filename = 'financial_intelligence_' . now()->format('Y_m_d') . '.json';
        $path = 'yuga/financial/' . $filename;

        Storage::disk('local')->put($path, json_encode($dataset, JSON_PRETTY_PRINT));

        $this->info("Financial dataset harvested: {$path}");
        $this->info("Transactions: " . count($transactions));
        $this->info("Subscriptions: " . count($subscriptions));

        return 0;
    }
}
