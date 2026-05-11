<?php

namespace App\Filament\Resources\AppModules\Pages;

use App\Filament\Resources\AppModules\AppModuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppModule extends EditRecord
{
    protected static string $resource = AppModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
