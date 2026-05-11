<?php

namespace App\Filament\Resources\CollectProjects;

use App\Filament\Resources\CollectProjects\Pages\CreateCollectProject;
use App\Filament\Resources\CollectProjects\Pages\EditCollectProject;
use App\Filament\Resources\CollectProjects\Pages\ListCollectProjects;
use App\Filament\Resources\CollectProjects\Schemas\CollectProjectForm;
use App\Filament\Resources\CollectProjects\Tables\CollectProjectsTable;
use App\Models\CollectProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CollectProjectResource extends Resource
{
    protected static ?string $model = CollectProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CollectProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectProjectsTable::configure($table);
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
            'index' => ListCollectProjects::route('/'),
            'create' => CreateCollectProject::route('/create'),
            'edit' => EditCollectProject::route('/{record}/edit'),
        ];
    }
}
