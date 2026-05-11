<?php

namespace App\Filament\Resources\InfrastructureNodes\Pages;

use App\Filament\Resources\InfrastructureNodes\InfrastructureNodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInfrastructureNode extends EditRecord
{
    protected static string $resource = InfrastructureNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
