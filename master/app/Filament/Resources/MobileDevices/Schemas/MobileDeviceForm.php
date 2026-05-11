<?php

namespace App\Filament\Resources\MobileDevices\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MobileDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('device_id')
                    ->required(),
                TextInput::make('model'),
                TextInput::make('os_version'),
                Toggle::make('is_secured')
                    ->required(),
                Toggle::make('remote_wipe_pending')
                    ->required(),
            ]);
    }
}
