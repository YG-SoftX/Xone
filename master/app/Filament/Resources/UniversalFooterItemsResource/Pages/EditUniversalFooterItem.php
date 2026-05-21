<?php

namespace App\Filament\Resources\UniversalFooterItemsResource\Pages;

use App\Filament\Resources\UniversalFooterItemsResource;
use Filament\Resources\Pages\EditRecord;

class EditUniversalFooterItem extends EditRecord
{
    protected static string $resource = UniversalFooterItemsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
