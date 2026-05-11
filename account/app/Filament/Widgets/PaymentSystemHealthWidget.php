<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PaymentSystemHealthWidget
 * 
 * Real-time health monitoring for the unified payment system.
 * Displays critical metrics for all payment providers.
 */
class PaymentSystemHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public function getStats(): array
    {
        $last24Hours = Carbon::now()->subDay();
        
        // Total transactions in last 24 hours
        $totalTransactions = DB::table('payment_transactions')
            ->where('created_at', '>=', $last24Hours)
            ->count();

        // Success rate
        $successfulTransactions = DB::table('payment_transactions')
            ->where('created_at', '>=', $last24Hours)
            ->where('status', 'completed')
            ->count();
        
        $successRate = $totalTransactions > 0 
            ? round(($successfulTransactions / $totalTransactions) * 100, 2) 
            : 100;

        // Total revenue (completed transactions)
        $totalRevenue = DB::table('payment_transactions')
            ->where('created_at', '>=', $last24Hours)
            ->where('status', 'completed')
            ->sum('amount');

        // Average response time (from metadata if available)
        $avgResponseTime = DB::table('payment_transactions')
            ->where('created_at', '>=', $last24Hours)
            ->whereNotNull('processing_time_ms')
            ->avg('processing_time_ms') ?? 0;

        // Active alerts count
        $activeAlerts = DB::table('security_alerts')
            ->where('resolved', false)
            ->where('created_at', '>=', $last24Hours)
            ->count();

        // Provider uptime (check last heartbeat)
        $providersDown = DB::table('payment_provider_configs')
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('last_health_check')
                    ->orWhere('last_health_check', '<', now()->subMinutes(5));
            })
            ->count();

        return [
            Stat::make('Total Transactions (24h)', number_format($totalTransactions))
                ->description('Last 24 hours')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($totalTransactions > 0 ? 'success' : 'gray'),

            Stat::make('Success Rate', "{$successRate}%")
                ->description('Transaction success rate')
                ->descriptionIcon($successRate >= 99 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($successRate >= 99 ? 'success' : ($successRate >= 95 ? 'warning' : 'danger')),

            Stat::make('Total Revenue', 'NPR ' . number_format($totalRevenue, 2))
                ->description('Completed transactions')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Avg Response Time', round($avgResponseTime, 0) . 'ms')
                ->description('Payment processing time')
                ->descriptionIcon($avgResponseTime < 200 ? 'heroicon-m-bolt' : 'heroicon-m-clock')
                ->color($avgResponseTime < 200 ? 'success' : ($avgResponseTime < 500 ? 'warning' : 'danger')),

            Stat::make('Active Alerts', $activeAlerts)
                ->description('Unresolved security alerts')
                ->descriptionIcon($activeAlerts === 0 ? 'heroicon-m-shield-check' : 'heroicon-m-bell-alert')
                ->color($activeAlerts === 0 ? 'success' : 'danger'),

            Stat::make('Providers Online', (6 - $providersDown) . '/6')
                ->description('Payment gateway status')
                ->descriptionIcon($providersDown === 0 ? 'heroicon-m-server' : 'heroicon-m-server-stack')
                ->color($providersDown === 0 ? 'success' : 'danger'),
        ];
    }
}
