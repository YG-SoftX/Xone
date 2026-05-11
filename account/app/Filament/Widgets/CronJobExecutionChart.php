<?php

namespace App\Filament\Widgets;

use App\Models\CronJob;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class CronJobExecutionChart extends ChartWidget
{
    protected static ?string $heading = 'Cron Job Execution History (Last 7 Days)';
    
    protected int|string|array $columnSpan = 'full';
    
    public ?string $filter = 'success_rate';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'total_runs' => 'Total Executions',
            'failed_runs' => 'Failed Executions',
            'success_rate' => 'Success Rate (%)',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? 'success_rate';
        
        // Get all cron jobs with execution data
        $jobs = CronJob::where('total_runs', '>', 0)
                       ->orderBy('total_runs', 'desc')
                       ->limit(5)
                       ->get();

        $datasets = [];
        $colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6'];

        foreach ($jobs as $index => $job) {
            // For now, we'll show cumulative data since we don't have daily breakdown
            // In production, you'd create a cron_job_executions pivot table for daily stats
            
            $data = match($filter) {
                'total_runs' => [$job->total_runs],
                'failed_runs' => [$job->failed_runs],
                'success_rate' => [$job->success_rate],
                default => [$job->success_rate],
            };

            $datasets[] = [
                'label' => $job->name,
                'data' => $data,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . '20', // Add transparency
                'fill' => true,
                'tension' => 0.4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => ['Current Status'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
