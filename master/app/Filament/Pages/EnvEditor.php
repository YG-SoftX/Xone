<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Filament\Notifications\Notification;

class EnvEditor extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Env Settings';
    protected static \BackedEnum|string|null $navigationGroup = 'Infrastructure';
    protected static ?int $navigationSort = 3;
    
    protected string $view = 'filament.pages.env-editor';

    public string $envContent = '';

    public function mount(): void
    {
        $this->envContent = File::get(base_path('.env'));
    }

    public function save(): void
    {
        File::put(base_path('.env'), $this->envContent);
        
        Notification::make()
            ->success()
            ->title('Environment updated')
            ->send();
    }
}
