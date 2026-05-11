<?php

namespace App\Filament\Resources\CollectForms;

use App\Filament\Resources\CollectForms\Pages\CreateCollectForm;
use App\Filament\Resources\CollectForms\Pages\EditCollectForm;
use App\Filament\Resources\CollectForms\Pages\ListCollectForms;
use App\Filament\Resources\CollectForms\Pages\ViewCollectForm;
use App\Filament\Resources\CollectForms\Schemas\CollectFormForm;
use App\Filament\Resources\CollectForms\Schemas\CollectFormInfolist;
use App\Filament\Resources\CollectForms\Tables\CollectFormsTable;
use App\Models\CollectForm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CollectFormResource extends Resource
{
    protected static ?string $model = CollectForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'yes';

    public static function form(Schema $schema): Schema
    {
        return CollectFormForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CollectFormInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectFormsTable::configure($table);
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
            'index' => ListCollectForms::route('/'),
            'create' => CreateCollectForm::route('/create'),
            'view' => ViewCollectForm::route('/{record}'),
            'edit' => EditCollectForm::route('/{record}/edit'),
        ];
    }
}
