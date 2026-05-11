<?php

namespace App\Filament\Resources\CollectForms\Pages;

use App\Filament\Resources\CollectForms\CollectFormResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCollectForm extends EditRecord
{
    protected static string $resource = CollectFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
