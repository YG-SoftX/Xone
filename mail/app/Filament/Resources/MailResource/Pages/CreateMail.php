<?php

namespace App\Filament\Resources\MailResource\Pages;

use App\Filament\Resources\MailResource;
use App\Jobs\SendEmail;
use App\Services\SpamProtection;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateMail extends CreateRecord
{
    protected static string $resource = MailResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $spamProtection = app(SpamProtection::class);

        $validation = $spamProtection->validateEmail($user->id, $data['to']);
        
        if (!$validation['allowed']) {
            Notification::make()
                ->title('Sending Failed')
                ->body($validation['reason'])
                ->danger()
                ->send();
                
            $this->halt();
        }

        $data['user_id'] = $user->id;
        $data['from'] = $user->email;
        $data['folder'] = 'sent';
        $data['read'] = true;

        // Append signature if exists
        $settings = \App\Models\MailSetting::where('user_id', $user->id)->first();
        if ($settings && $settings->signature) {
            $data['body'] .= '<br><br>' . $settings->signature;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->folder === 'drafts') {
            Notification::make()
                ->title('Draft Saved')
                ->success()
                ->send();
            return;
        }

        $delay = null;
        if ($record->scheduled_at && $record->scheduled_at->isFuture()) {
            $delay = $record->scheduled_at;
        }
        
        $job = SendEmail::dispatch(
            to: $record->to,
            subject: $record->subject,
            body: $record->body,
            fromEmail: $record->from,
            attachments: [],
            mailRecordId: $record->id
        );

        if ($delay) {
            $job->delay($delay);
            Notification::make()
                ->title('Email Scheduled')
                ->body("Your email will be sent on " . $delay->format('d M Y, H:i'))
                ->success()
                ->send();
        }

        app(SpamProtection::class)->recordEmailSent(Auth::id());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
