<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * QuickActionsWidget
 * 
 * Provides quick access to common admin actions.
 */
class QuickActionsWidget extends Widget
{
    protected int | string | array $columnSpan = 'full';
    
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.widgets.quick-actions-widget', [
            'actions' => $this->getActions(),
        ]);
    }

    public function getActions(): array
    {
        return [
            [
                'label' => 'Run Health Check',
                'icon' => 'heroicon-o-heart',
                'color' => 'primary',
                'action' => 'checkHealth',
            ],
            [
                'label' => 'Create Backup',
                'icon' => 'heroicon-o-database',
                'color' => 'success',
                'action' => 'createBackup',
            ],
            [
                'label' => 'Deploy All Services',
                'icon' => 'heroicon-o-rocket-launch',
                'color' => 'warning',
                'action' => 'deployAll',
                'requiresConfirmation' => true,
            ],
            [
                'label' => 'Clear All Caches',
                'icon' => 'heroicon-o-trash',
                'color' => 'danger',
                'action' => 'clearCache',
                'requiresConfirmation' => true,
            ],
        ];
    }

    public function checkHealth(): void
    {
        dispatch(new \App\Jobs\HealthCheckJob());
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Health check initiated for all services',
        ]);
    }

    public function createBackup(): void
    {
        dispatch(new \App\Jobs\BackupDatabaseJob());
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Database backup initiated',
        ]);
    }

    public function deployAll(): void
    {
        dispatch(new \App\Jobs\DeployServiceJob(null, []));
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Bulk deployment initiated',
        ]);
    }

    public function clearCache(): void
    {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'All caches cleared successfully',
        ]);
    }
}
