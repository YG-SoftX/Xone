<?php

namespace App\Filament\Resources\ThirdPartyApps;

use App\Filament\Resources\ThirdPartyApps\Pages\CreateThirdPartyApp;
use App\Filament\Resources\ThirdPartyApps\Pages\EditThirdPartyApp;
use App\Filament\Resources\ThirdPartyApps\Pages\ListThirdPartyApps;
use App\Filament\Resources\ThirdPartyApps\Schemas\ThirdPartyAppForm;
use App\Filament\Resources\ThirdPartyApps\Tables\ThirdPartyAppsTable;
use App\Models\ThirdPartyApp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ThirdPartyAppResource extends Resource
{
    protected static ?string $model = ThirdPartyApp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ThirdPartyAppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThirdPartyAppsTable::configure($table);
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
            'index' => ListThirdPartyApps::route('/'),
            'create' => CreateThirdPartyApp::route('/create'),
            'edit' => EditThirdPartyApp::route('/{record}/edit'),
        ];
    }
}
