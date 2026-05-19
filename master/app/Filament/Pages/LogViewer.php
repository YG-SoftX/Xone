<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class LogViewer extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'System Logs';
    protected static \BackedEnum|string|null $navigationGroup = 'Infrastructure';
    protected static ?int $navigationSort = 4;
    
    protected string $view = 'filament.pages.log-viewer';

    public string $logs = '';

    public function mount(): void
    {
        $logFile = storage_path('logs/laravel.log');
        if (File::exists($logFile)) {
            $this->logs = File::get($logFile);
        } else {
            $this->logs = 'No logs found.';
        }
    }
}
