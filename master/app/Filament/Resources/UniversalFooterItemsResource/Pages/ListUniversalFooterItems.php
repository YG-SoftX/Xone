<?php

namespace App\Filament\Resources\UniversalFooterItemsResource\Pages;

use App\Filament\Resources\UniversalFooterItemsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUniversalFooterItems extends ListRecords
{
    protected static string $resource = UniversalFooterItemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
