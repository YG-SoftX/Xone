<?php

namespace App\Filament\Resources\SupportArticles\Pages;

use App\Filament\Resources\SupportArticles\SupportArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportArticle extends EditRecord
{
    protected static string $resource = SupportArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
