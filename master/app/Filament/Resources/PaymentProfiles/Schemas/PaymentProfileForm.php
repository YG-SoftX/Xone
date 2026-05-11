<?php

namespace App\Filament\Resources\PaymentProfiles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tenant_id')
                    ->numeric(),
                TextInput::make('provider')
                    ->required()
                    ->default('fonepay'),
                TextInput::make('account_reference')
                    ->required(),
                Toggle::make('is_verified')
                    ->required(),
            ]);
    }
}
