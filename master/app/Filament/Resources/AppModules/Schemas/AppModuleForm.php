<?php

namespace App\Filament\Resources\AppModules\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AppModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('icon')
                    ->required()
                    ->default('heroicon-o-cube'),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_core')
                    ->required(),
                TextInput::make('base_url')
                    ->url(),
            ]);
    }
}
