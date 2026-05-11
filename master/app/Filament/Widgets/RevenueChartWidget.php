<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;

/**
 * RevenueChartWidget
 * 
 * Displays monthly revenue trends from subscription data.
 */
class RevenueChartWidget extends ChartWidget
{
    public function getHeading(): ?string
    {
        return 'Monthly Revenue';
    }

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Get last 6 months of revenue data
        $months = [];
        $revenue = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            // Query subscription revenue for this month
            $monthlyRevenue = Subscription::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
            
            $revenue[] = round($monthlyRevenue / 1000, 2); // Convert to thousands
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (in thousands)',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $months,
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
                        'callback' => 'function(value) { return "$" + value + "k"; }',
                    ],
                ],
            ],
        ];
    }
}
