<?php

namespace App\Filament\Widgets;

use App\Models\CronJob;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CronHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 10;
    
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalJobs = CronJob::count();
        $enabledJobs = CronJob::where('is_enabled', true)->count();
        $failedJobs = CronJob::where('status', 'failed')->count();
        $avgSuccessRate = CronJob::where('total_runs', '>', 0)
            ->get()
            ->avg('success_rate') ?? 100;

        return [
            Stat::make('Total Cron Jobs', $totalJobs)
                ->description('Configured jobs')
                ->descriptionIcon('heroicon-o-clock')
                ->color('primary'),
            
            Stat::make('Active Jobs', $enabledJobs)
                ->description('Currently enabled')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Failed Jobs', $failedJobs)
                ->description('Need attention')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($failedJobs > 0 ? 'danger' : 'success'),
            
            Stat::make('Avg Success Rate', number_format($avgSuccessRate, 1) . '%')
                ->description('Overall health')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($avgSuccessRate >= 90 ? 'success' : ($avgSuccessRate >= 70 ? 'warning' : 'danger')),
        ];
    }
}
