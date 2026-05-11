<?php

namespace App\Filament\Resources\PaymentProfiles\Pages;

use App\Filament\Resources\PaymentProfiles\PaymentProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentProfiles extends ListRecords
{
    protected static string $resource = PaymentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
