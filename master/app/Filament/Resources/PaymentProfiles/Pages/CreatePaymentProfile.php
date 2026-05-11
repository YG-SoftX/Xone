<?php

namespace App\Filament\Resources\PaymentProfiles\Pages;

use App\Filament\Resources\PaymentProfiles\PaymentProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentProfile extends CreateRecord
{
    protected static string $resource = PaymentProfileResource::class;
}
