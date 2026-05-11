<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PaymentTransactionsChartWidget
 * 
 * Displays transaction volume trends over time.
 * Shows hourly/daily/weekly patterns.
 */
class PaymentTransactionsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Transaction Volume (Last 7 Days)';
    protected static ?int $sort = 2;

    public function getData(): array
    {
        $days = 7;
        $startDate = Carbon::now()->subDays($days);
        
        // Get daily transaction counts
        $transactions = DB::table('payment_transactions')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing dates with zero values
        $labels = [];
        $totalData = [];
        $successfulData = [];
        $failedData = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');
            
            $dayData = $transactions->firstWhere('date', $date);
            
            $totalData[] = $dayData ? (int)$dayData->count : 0;
            $successfulData[] = $dayData ? (int)$dayData->successful : 0;
            $failedData[] = $dayData ? (int)$dayData->failed : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Transactions',
                    'data' => $totalData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Successful',
                    'data' => $successfulData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Failed',
                    'data' => $failedData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 10,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
