<?php

namespace App\Filament\Resources\MobileDevices;

use App\Filament\Resources\MobileDevices\Pages\CreateMobileDevice;
use App\Filament\Resources\MobileDevices\Pages\EditMobileDevice;
use App\Filament\Resources\MobileDevices\Pages\ListMobileDevices;
use App\Filament\Resources\MobileDevices\Schemas\MobileDeviceForm;
use App\Filament\Resources\MobileDevices\Tables\MobileDevicesTable;
use App\Models\MobileDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MobileDeviceResource extends Resource
{
    protected static ?string $model = MobileDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MobileDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MobileDevicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMobileDevices::route('/'),
            'create' => CreateMobileDevice::route('/create'),
            'edit' => EditMobileDevice::route('/{record}/edit'),
        ];
    }
}
