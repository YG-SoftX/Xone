<?php

namespace App\Filament\Widgets;

use App\Services\PagerDutyNotifier;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PagerDutyStatusWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s'; // Refresh every minute
    
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $pagerDuty = app(PagerDutyNotifier::class);
        
        if (!$pagerDuty->isConfigured()) {
            return [
                Stat::make('PagerDuty', 'Not Configured')
                    ->description('Configure PAGERDUTY_ROUTING_KEY in .env')
                    ->color('gray'),
            ];
        }

        // Get on-call status
        $onCallStatus = $pagerDuty->getOnCallStatus();
        
        $activeIncidents = $this->getActiveIncidentCount();
        $acknowledgedIncidents = $this->getAcknowledgedIncidentCount();
        $resolvedToday = $this->getResolvedTodayCount();

        return [
            Stat::make('Active Incidents', $activeIncidents)
                ->description($activeIncidents > 0 ? 'Requires attention' : 'All clear')
                ->color($activeIncidents > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle'),
            
            Stat::make('Acknowledged', $acknowledgedIncidents)
                ->description('Engineers responding')
                ->color('warning')
                ->icon('heroicon-o-check-circle'),
            
            Stat::make('Resolved Today', $resolvedToday)
                ->description('Last 24 hours')
                ->color('primary')
                ->icon('heroicon-o-shield-check'),
            
            Stat::make('On-Call Schedules', count($onCallStatus['schedules'] ?? []))
                ->description('Active rotations')
                ->color('info')
                ->icon('heroicon-o-calendar'),
        ];
    }

    protected function getActiveIncidentCount(): int
    {
        // TODO: Query PagerDuty API for active incidents
        // For now, return mock data based on recent cron failures
        return \App\Models\CronJob::where('failed_runs', '>', 0)
            ->where('success_rate', '<', 70)
            ->count();
    }

    protected function getAcknowledgedIncidentCount(): int
    {
        // TODO: Query PagerDuty API
        return 0;
    }

    protected function getResolvedTodayCount(): int
    {
        // TODO: Query PagerDuty API
        return \App\Models\CronJob::whereDate('updated_at', today())->count();
    }
}
