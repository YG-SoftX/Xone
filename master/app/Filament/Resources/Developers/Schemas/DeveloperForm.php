<?php

namespace App\Filament\Resources\Developers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DeveloperForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('company'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('api_key'),
            ]);
    }
}
