<?php

namespace App\Filament\Resources\YgServiceResource\Pages;

use App\Filament\Resources\YgServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYgService extends EditRecord
{
    protected static string $resource = YgServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
