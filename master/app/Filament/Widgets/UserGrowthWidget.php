<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * UserGrowthWidget
 * 
 * Displays user acquisition and growth metrics.
 */
class UserGrowthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $totalUsers = \App\Models\User::count();
        $activeUsers = \App\Models\User::where('last_active_at', '>=', now()->subDays(30))->count();
        $newUsersThisMonth = \App\Models\User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $previousMonthUsers = \App\Models\User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growthRate = $previousMonthUsers > 0
            ? round((($newUsersThisMonth - $previousMonthUsers) / $previousMonthUsers) * 100, 1)
            : 0;

        $activePercentage = $totalUsers > 0
            ? round(($activeUsers / $totalUsers) * 100, 1)
            : 0;

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description('Across ecosystem')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Active Users', number_format($activeUsers))
                ->description("{$activePercentage}% active (30 days)")
                ->descriptionIcon('heroicon-o-user-check')
                ->color('success'),

            Stat::make('New This Month', number_format($newUsersThisMonth))
                ->description($growthRate >= 0 ? "+{$growthRate}% vs last month" : "{$growthRate}% vs last month")
                ->descriptionIcon('heroicon-o-user-plus')
                ->color($growthRate >= 0 ? 'success' : 'warning'),
        ];
    }
}
