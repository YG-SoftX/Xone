<?php

namespace App\Filament\Resources\CollectSubmissions\Pages;

use App\Filament\Resources\CollectSubmissions\CollectSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollectSubmission extends EditRecord
{
    protected static string $resource = CollectSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
