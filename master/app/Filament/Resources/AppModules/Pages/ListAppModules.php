<?php

namespace App\Filament\Resources\AppModules\Pages;

use App\Filament\Resources\AppModules\AppModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppModules extends ListRecords
{
    protected static string $resource = AppModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
