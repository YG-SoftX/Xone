<?php

namespace App\Filament\Widgets;

use App\Models\AppModule;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * ServiceHealthWidget
 * 
 * Displays real-time service health statistics on the dashboard.
 */
class ServiceHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalServices = AppModule::count();
        $healthyServices = AppModule::where('status', 'healthy')->count();
        $degradedServices = AppModule::where('status', 'degraded')->count();
        $unhealthyServices = AppModule::where('status', 'unhealthy')->count();

        $healthPercentage = $totalServices > 0 
            ? round(($healthyServices / $totalServices) * 100, 1)
            : 0;

        return [
            Stat::make('Total Services', $totalServices)
                ->description('Registered in ecosystem')
                ->descriptionIcon('heroicon-o-server')
                ->color('primary'),

            Stat::make('Healthy', $healthyServices)
                ->description("{$healthPercentage}% operational")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([$healthyServices]),

            Stat::make('Degraded', $degradedServices)
                ->description('Performance issues')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->chart([$degradedServices]),

            Stat::make('Unhealthy', $unhealthyServices)
                ->description('Requires attention')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->chart([$unhealthyServices]),
        ];
    }
}
