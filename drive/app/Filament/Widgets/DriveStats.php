<?php

namespace App\Filament\Widgets;

use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Models\FileShare;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DriveStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalFiles   = DriveFile::count();
        $totalSize    = DriveFile::sum('size');
        $totalUsers   = User::count();
        $totalFolders = DriveFolder::count();
        $totalShares  = FileShare::count();
        $newToday     = DriveFile::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Files', number_format($totalFiles))
                ->description("{$newToday} uploaded today")
                ->descriptionIcon('heroicon-m-document')
                ->color('primary'),
            Stat::make('Storage Used', $this->formatBytes($totalSize))
                ->description('Across all users')
                ->descriptionIcon('heroicon-m-server')
                ->color('info'),
            Stat::make('Total Users', number_format($totalUsers))
                ->description('With drive access')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Folders', number_format($totalFolders))
                ->description('Organized folders')
                ->descriptionIcon('heroicon-m-folder')
                ->color('warning'),
            Stat::make('Shared Files', number_format($totalShares))
                ->description('Active shares')
                ->descriptionIcon('heroicon-m-share')
                ->color('secondary'),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1099511627776) return round($bytes / 1099511627776, 2) . ' TB';
        if ($bytes >= 1073741824)    return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)       return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)          return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
