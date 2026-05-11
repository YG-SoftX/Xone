<?php

namespace App\Filament\Resources\MobileDevices\Pages;

use App\Filament\Resources\MobileDevices\MobileDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMobileDevices extends ListRecords
{
    protected static string $resource = MobileDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
