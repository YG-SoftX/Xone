<?php

namespace App\Filament\Resources\MailRuleResource\Pages;

use App\Filament\Resources\MailRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageMailRules extends ManageRecords
{
    protected static string $resource = MailRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = Auth::id();
                    return $data;
                }),
        ];
    }
}
