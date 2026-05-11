<?php

namespace App\Filament\Resources\CollectProjects\Pages;

use App\Filament\Resources\CollectProjects\CollectProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectProjects extends ListRecords
{
    protected static string $resource = CollectProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
