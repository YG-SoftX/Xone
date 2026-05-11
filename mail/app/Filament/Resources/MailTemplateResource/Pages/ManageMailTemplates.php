<?php

namespace App\Filament\Resources\MailTemplateResource\Pages;

use App\Filament\Resources\MailTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageMailTemplates extends ManageRecords
{
    protected static string $resource = MailTemplateResource::class;

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
