<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class MigrationManager extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-command-line';
    protected static ?string $navigationLabel = 'Migrations';
    protected static \BackedEnum|string|null $navigationGroup = 'Infrastructure';
    protected static ?int $navigationSort = 2;
    
    protected string $view = 'filament.pages.migration-manager';

    public string $output = '';

    public function runMigrations(): void
    {
        Artisan::call('migrate', ['--force' => true]);
        $this->output = Artisan::output();
        
        Notification::make()
            ->success()
            ->title('Migrations executed successfully')
            ->send();
    }
}
