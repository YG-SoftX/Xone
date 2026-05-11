<?php

namespace App\Filament\Resources\YgServiceResource\Pages;

use App\Filament\Resources\YgServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYgServices extends ListRecords
{
    protected static string $resource = YgServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
