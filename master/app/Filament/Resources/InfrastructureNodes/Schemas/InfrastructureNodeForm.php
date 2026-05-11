<?php

namespace App\Filament\Resources\InfrastructureNodes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InfrastructureNodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('ip_address'),
                TextInput::make('region')
                    ->required()
                    ->default('us-east'),
                TextInput::make('type')
                    ->required()
                    ->default('app_server'),
                TextInput::make('status')
                    ->required()
                    ->default('running'),
                TextInput::make('cpu_usage')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
