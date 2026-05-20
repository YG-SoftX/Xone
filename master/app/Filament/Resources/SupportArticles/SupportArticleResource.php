<?php

namespace App\Filament\Resources\SupportArticles;

use App\Filament\Resources\SupportArticles\Pages\CreateSupportArticle;
use App\Filament\Resources\SupportArticles\Pages\EditSupportArticle;
use App\Filament\Resources\SupportArticles\Pages\ListSupportArticles;
use App\Filament\Resources\SupportArticles\Schemas\SupportArticleForm;
use App\Filament\Resources\SupportArticles\Tables\SupportArticlesTable;
use App\Models\Support\Article;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class SupportArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Ecosystem Management';

    protected static ?string $navigationLabel = 'Knowledge Base';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return SupportArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportArticlesTable::configure($table);
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
            'index' => ListSupportArticles::route('/'),
            'create' => CreateSupportArticle::route('/create'),
            'edit' => EditSupportArticle::route('/{record}/edit'),
        ];
    }
}
