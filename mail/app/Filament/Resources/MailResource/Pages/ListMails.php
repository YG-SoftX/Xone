<?php

namespace App\Filament\Resources\MailResource\Pages;

use App\Filament\Resources\MailResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMails extends ListRecords
{
    protected static string $resource = MailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Compose Email')
                ->icon('heroicon-o-pencil-square'),
        ];
    }

    public function getTablePollingInterval(): ?string
    {
        return '30s'; 
    }

    protected function afterTablePoll(): void
    {
        $lastCheckedId = session('last_mail_id');
        $newMail = \App\Models\Mail::where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('folder', 'inbox')
            ->where('id', '>', $lastCheckedId ?? 0)
            ->latest()
            ->first();

        if ($newMail) {
            session(['last_mail_id' => $newMail->id]);

            Notification::make()
                ->title('New Mail from ' . $newMail->from)
                ->body($newMail->subject)
                ->icon('heroicon-o-envelope')
                ->iconColor('success')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('reply')
                        ->button()
                        ->color('success')
                        ->url(MailResource::getUrl('view', ['record' => $newMail->id])),
                ])
                ->send();
        }
    }

    public function getTabs(): array
    {
        return [
            'inbox' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'inbox')),
            'sent' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'sent')),
            'drafts' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'drafts')),
            'trash' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'trash')),
            'spam' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'spam')),
            'finance' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'finance')),
            'important' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'important')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'inbox';
    }
}
