<?php

namespace App\Filament\Resources\ThirdPartyApps\Pages;

use App\Filament\Resources\ThirdPartyApps\ThirdPartyAppResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditThirdPartyApp extends EditRecord
{
    protected static string $resource = ThirdPartyAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
