<?php

namespace App\Filament\Resources\AppModules;

use App\Filament\Resources\AppModules\Pages\CreateAppModule;
use App\Filament\Resources\AppModules\Pages\EditAppModule;
use App\Filament\Resources\AppModules\Pages\ListAppModules;
use App\Filament\Resources\AppModules\Schemas\AppModuleForm;
use App\Filament\Resources\AppModules\Tables\AppModulesTable;
use App\Models\AppModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppModuleResource extends Resource
{
    protected static ?string $model = AppModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AppModuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppModulesTable::configure($table);
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
            'index' => ListAppModules::route('/'),
            'create' => CreateAppModule::route('/create'),
            'edit' => EditAppModule::route('/{record}/edit'),
        ];
    }
}
