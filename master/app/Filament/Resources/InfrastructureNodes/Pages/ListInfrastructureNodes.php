<?php

namespace App\Filament\Resources\InfrastructureNodes\Pages;

use App\Filament\Resources\InfrastructureNodes\InfrastructureNodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInfrastructureNodes extends ListRecords
{
    protected static string $resource = InfrastructureNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
