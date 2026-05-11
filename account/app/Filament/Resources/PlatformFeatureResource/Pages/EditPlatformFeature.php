<?php

namespace App\Filament\Resources\PlatformFeatureResource\Pages;

use App\Filament\Resources\PlatformFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlatformFeature extends EditRecord
{
    protected static string $resource = PlatformFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
