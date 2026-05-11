<?php

namespace App\Filament\Resources\CollectForms\Pages;

use App\Filament\Resources\CollectForms\CollectFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectForms extends ListRecords
{
    protected static string $resource = CollectFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
