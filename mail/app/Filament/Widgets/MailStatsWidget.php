<?php

namespace App\Filament\Widgets;

use App\Models\Mail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MailStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        return [
            Stat::make('Total Emails', Mail::where('user_id', $userId)->count())
                ->description('All folders')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),
            Stat::make('Unread Emails', Mail::where('user_id', $userId)->where('read', false)->count())
                ->description('Action required')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
            Stat::make('Spam Blocked', Mail::where('user_id', $userId)->where('folder', 'spam')->count())
                ->description('Potential threats')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color('warning'),
            Stat::make('Sent This Week', Mail::where('user_id', $userId)->where('folder', 'sent')->where('created_at', '>=', now()->startOfWeek())->count())
                ->description('Outbound activity')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color('success'),
            Stat::make('Daily Quota', function() use ($userId) {
                $quota = \App\Models\EmailQuota::where('user_id', $userId)->where('date', now()->toDateString())->first();
                $sent = $quota?->daily_sent ?? 0;
                $limit = $quota?->daily_limit ?? 100;
                return "{$sent} / {$limit}";
            })
                ->description('Remaining: ' . (function() use ($userId) {
                    $quota = \App\Models\EmailQuota::where('user_id', $userId)->where('date', now()->toDateString())->first();
                    return $quota ? $quota->remainingQuota() : 100;
                })())
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}
