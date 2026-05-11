<?php

namespace App\Filament\Widgets;

use App\Models\InfrastructureNode;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EcosystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTenants = Tenant::count();
        $totalUsers   = User::count();
        $activeSubs   = Subscription::where('status', 'active')->count();
        $activeNodes  = InfrastructureNode::where('status', 'running')->count();

        // 7-day user growth sparkline
        $userGrowth = collect(range(6, 0))->map(
            fn($d) => User::whereDate('created_at', now()->subDays($d))->count()
        )->toArray();

        return [
            Stat::make('Ecosystem Tenants', $totalTenants)
                ->description('Active workspaces & businesses')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),

            Stat::make('Global Users', number_format($totalUsers))
                ->description('Registered across all tenants')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart($userGrowth),

            Stat::make('Active Subscriptions', $activeSubs)
                ->description('Est. MRR: $' . number_format($activeSubs * 10))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('info'),

            Stat::make('Infrastructure Nodes', $activeNodes)
                ->description($activeNodes > 0 ? 'All systems operational' : 'No running nodes')
                ->descriptionIcon('heroicon-m-server')
                ->color($activeNodes > 0 ? 'success' : 'danger'),
        ];
    }
}
