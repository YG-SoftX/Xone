<?php

namespace App\Filament\Resources\PaymentProfiles\Pages;

use App\Filament\Resources\PaymentProfiles\PaymentProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentProfile extends EditRecord
{
    protected static string $resource = PaymentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
