<?php

namespace App\Filament\Resources\MobileDevices\Pages;

use App\Filament\Resources\MobileDevices\MobileDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMobileDevice extends EditRecord
{
    protected static string $resource = MobileDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
