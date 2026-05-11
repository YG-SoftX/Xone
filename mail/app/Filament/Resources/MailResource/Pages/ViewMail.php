<?php

namespace App\Filament\Resources\MailResource\Pages;

use App\Filament\Resources\MailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMail extends ViewRecord
{
    protected static string $resource = MailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
