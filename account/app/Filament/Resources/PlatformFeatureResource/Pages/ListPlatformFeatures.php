<?php

namespace App\Filament\Resources\PlatformFeatureResource\Pages;

use App\Filament\Resources\PlatformFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformFeatures extends ListRecords
{
    protected static string $resource = PlatformFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
