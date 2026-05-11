<?php

namespace App\Filament\Widgets;

use App\Services\WebhookDispatcher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WebhookDeliveryWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '120s';
    
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $dispatcher = app(WebhookDispatcher::class);
        $stats = $dispatcher->getDeliveryStats();
        
        $totalDeliveries = $stats['successful_deliveries'] + $stats['failed_deliveries'];
        $successRate = $totalDeliveries > 0 
            ? round(($stats['successful_deliveries'] / $totalDeliveries) * 100, 1)
            : 100;

        return [
            Stat::make('Today\'s Deliveries', $stats['total_deliveries_today'])
                ->description('Webhook events sent')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane'),
            
            Stat::make('Success Rate', $successRate . '%')
                ->description($successRate > 95 ? 'Excellent' : 'Needs attention')
                ->color($successRate > 95 ? 'success' : 'warning')
                ->icon('heroicon-o-check-badge'),
            
            Stat::make('Failed Deliveries', $stats['failed_deliveries'])
                ->description('Requires investigation')
                ->color($stats['failed_deliveries'] > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),
            
            Stat::make('Avg Response Time', $stats['average_response_time_ms'] . 'ms')
                ->description('Webhook endpoint speed')
                ->color($stats['average_response_time_ms'] < 500 ? 'success' : 'warning')
                ->icon('heroicon-o-stopwatch'),
        ];
    }
}
