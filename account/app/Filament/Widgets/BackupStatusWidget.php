<?php

namespace App\Filament\Widgets;

use App\Services\BackupVerifier;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BackupStatusWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 20;
    
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $verifier = app(BackupVerifier::class);
        $stats = $verifier->getBackupStats();
        $verification = $verifier->verifyLatestBackup();

        return [
            Stat::make('Total Backups', $stats['total_backups'])
                ->description("{$stats['total_size_mb']} MB total")
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color('primary'),
            
            Stat::make('Latest Backup', $stats['newest_backup'] ?? 'None')
                ->description($verification['verified'] ? '✅ Verified' : '❌ Not verified')
                ->descriptionIcon($verification['verified'] ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                ->color($verification['verified'] ? 'success' : 'danger'),
            
            Stat::make('Retention Period', "{$stats['retention_days']} days")
                ->description("Avg size: {$stats['average_size_mb']} MB")
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),
            
            Stat::make('Last Verification', $this->getLastVerificationTime($verification))
                ->description($verification['verified'] ? 'Integrity OK' : 'Issues detected')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($verification['verified'] ? 'success' : 'warning'),
        ];
    }

    protected function getLastVerificationTime(array $verification): string
    {
        if (!isset($verification['file'])) {
            return 'Never';
        }

        $fileTime = filemtime($verification['file']);
        
        if ($fileTime === false) {
            return 'Unknown';
        }

        return now()->setTimestamp($fileTime)->diffForHumans(short: true);
    }
}
