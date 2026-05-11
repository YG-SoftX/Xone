<?php

namespace App\Filament\Resources\CollectSubmissions\Pages;

use App\Filament\Resources\CollectSubmissions\CollectSubmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollectSubmissions extends ListRecords
{
    protected static string $resource = CollectSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
