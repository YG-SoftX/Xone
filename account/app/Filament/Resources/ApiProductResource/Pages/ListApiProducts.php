<?php

namespace App\Filament\Resources\ApiProductResource\Pages;

use App\Filament\Resources\ApiProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApiProducts extends ListRecords
{
    protected static string $resource = ApiProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
