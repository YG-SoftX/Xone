<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use App\Models\KYC;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers    = User::count();
        $activeUsers   = User::where('status', 'active')->count();
        $pendingKyc    = KYC::where('status', 'pending')->count();
        $openTickets   = SupportTicket::where('status', 'open')->count();
        $totalRevenue  = Transaction::where('status', 'completed')->where('type', 'payment')->sum('amount');
        $newUsersToday = User::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description("{$activeUsers} active")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
            Stat::make('New Today', $newUsersToday)
                ->description('Registered today')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),
            Stat::make('Pending KYC', $pendingKyc)
                ->description('Awaiting verification')
                ->descriptionIcon('heroicon-m-identification')
                ->color($pendingKyc > 0 ? 'warning' : 'success'),
            Stat::make('Open Tickets', $openTickets)
                ->description('Support requests')
                ->descriptionIcon('heroicon-m-chat-bubble-left')
                ->color($openTickets > 0 ? 'danger' : 'success'),
            Stat::make('Total Revenue', 'NPR ' . number_format($totalRevenue, 2))
                ->description('From payments')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
