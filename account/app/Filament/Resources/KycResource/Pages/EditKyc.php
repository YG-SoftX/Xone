<?php

namespace App\Filament\Resources\KycResource\Pages;

use App\Filament\Resources\KycResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKyc extends EditRecord
{
    protected static string $resource = KycResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
