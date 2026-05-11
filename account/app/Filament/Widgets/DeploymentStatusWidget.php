<?php

namespace App\Filament\Widgets;

use App\Services\DeploymentRollback;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeploymentStatusWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $rollbackService = app(DeploymentRollback::class);
        $availableRollbacks = $rollbackService->getAvailableRollbacks();
        
        $lastDeployment = !empty($availableRollbacks) ? $availableRollbacks[0] : null;
        $autoRollbackEnabled = config('deployment.auto_rollback_enabled', false);

        return [
            Stat::make('Available Rollbacks', count($availableRollbacks))
                ->description('Previous deployments')
                ->color(count($availableRollbacks) > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-arrow-uturn-left'),
            
            Stat::make('Last Deployment', $lastDeployment ? 'Available' : 'None')
                ->description($lastDeployment ? substr($lastDeployment['timestamp'], 0, 16) : 'No backups')
                ->color($lastDeployment ? 'info' : 'warning')
                ->icon('heroicon-o-clock'),
            
            Stat::make('Auto-Rollback', $autoRollbackEnabled ? 'Enabled' : 'Disabled')
                ->description($autoRollbackEnabled ? 'Critical failures trigger rollback' : 'Manual rollback only')
                ->color($autoRollbackEnabled ? 'warning' : 'success')
                ->icon('heroicon-o-shield-check'),
            
            Stat::make('Backup Retention', config('deployment.backup_retention_days', 30) . ' days')
                ->description('Storage policy')
                ->color('primary')
                ->icon('heroicon-o-archive-box'),
        ];
    }
}
