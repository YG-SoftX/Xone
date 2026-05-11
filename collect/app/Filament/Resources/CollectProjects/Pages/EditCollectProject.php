<?php

namespace App\Filament\Resources\CollectProjects\Pages;

use App\Filament\Resources\CollectProjects\CollectProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollectProject extends EditRecord
{
    protected static string $resource = CollectProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
