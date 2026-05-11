<?php

namespace App\Filament\Widgets;

use App\Services\AnomalyDetector;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnomalyDetectionWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '300s'; // Refresh every 5 minutes
    
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $detector = app(AnomalyDetector::class);
        $stats = $detector->getAnomalyStats();
        
        $totalAnomalies = $stats['critical_anomalies'] + 
                         $stats['high_anomalies'] + 
                         $stats['medium_anomalies'] +
                         $stats['low_anomalies'];

        return [
            Stat::make('Critical Anomalies', $stats['critical_anomalies'])
                ->description('Requires immediate attention')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
            
            Stat::make('High Risk Jobs', count($stats['most_affected_jobs']))
                ->description('Risk score >10')
                ->color('warning')
                ->icon('heroicon-o-chart-bar'),
            
            Stat::make('Total Anomalies', $totalAnomalies)
                ->description('Last 24 hours')
                ->color($totalAnomalies > 5 ? 'warning' : 'success')
                ->icon('heroicon-o-bell'),
            
            Stat::make('Jobs Analyzed', $stats['total_jobs_analyzed'])
                ->description('With ML detection')
                ->color('info')
                ->icon('heroicon-o-cpu-chip'),
        ];
    }
}
