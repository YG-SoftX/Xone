<?php

namespace App\Filament\Resources\ApiProductResource\Pages;

use App\Filament\Resources\ApiProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiProduct extends EditRecord
{
    protected static string $resource = ApiProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
