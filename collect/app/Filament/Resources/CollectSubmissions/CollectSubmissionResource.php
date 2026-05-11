<?php

namespace App\Filament\Resources\CollectSubmissions;

use App\Filament\Resources\CollectSubmissions\Pages\CreateCollectSubmission;
use App\Filament\Resources\CollectSubmissions\Pages\EditCollectSubmission;
use App\Filament\Resources\CollectSubmissions\Pages\ListCollectSubmissions;
use App\Filament\Resources\CollectSubmissions\Schemas\CollectSubmissionForm;
use App\Filament\Resources\CollectSubmissions\Tables\CollectSubmissionsTable;
use App\Models\CollectSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CollectSubmissionResource extends Resource
{
    protected static ?string $model = CollectSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CollectSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CollectSubmissionsTable::configure($table);
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
            'index' => ListCollectSubmissions::route('/'),
            'create' => CreateCollectSubmission::route('/create'),
            'edit' => EditCollectSubmission::route('/{record}/edit'),
        ];
    }
}
