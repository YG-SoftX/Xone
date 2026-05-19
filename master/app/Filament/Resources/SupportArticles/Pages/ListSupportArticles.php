<?php

namespace App\Filament\Resources\SupportArticles\Pages;

use App\Filament\Resources\SupportArticles\SupportArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportArticles extends ListRecords
{
    protected static string $resource = SupportArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
