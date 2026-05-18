<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('plan_name')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->default(0.00)
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('expires_at'),
            ]);
    }
}
