<?php

namespace App\Filament\Resources\ThirdPartyApps\Pages;

use App\Filament\Resources\ThirdPartyApps\ThirdPartyAppResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThirdPartyApps extends ListRecords
{
    protected static string $resource = ThirdPartyAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
