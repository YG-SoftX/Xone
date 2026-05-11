<?php

namespace App\Filament\Resources\ThirdPartyApps\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ThirdPartyAppForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('developer_id')
                    ->relationship('developer', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('version')
                    ->required()
                    ->default('1.0.0'),
                TextInput::make('status')
                    ->required()
                    ->default('under_review'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
            ]);
    }
}
