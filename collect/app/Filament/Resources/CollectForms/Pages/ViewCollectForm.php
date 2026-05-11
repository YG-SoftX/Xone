<?php

namespace App\Filament\Resources\CollectForms\Pages;

use App\Filament\Resources\CollectForms\CollectFormResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCollectForm extends ViewRecord
{
    protected static string $resource = CollectFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
