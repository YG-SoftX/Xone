<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Handle agent reply if provided
        $replyMessage = $this->data['_reply_message'] ?? null;

        if ($replyMessage && trim($replyMessage) !== '') {
            $record = $this->record;
            $messages = $record->messages ?? [];

            $messages[] = [
                'role' => 'agent',
                'body' => $replyMessage,
                'agent_name' => auth()->user()?->name ?? 'Support Agent',
                'created_at' => now()->toISOString(),
            ];

            // Re-open ticket if it was closed
            if (in_array($record->status, ['resolved', 'closed'])) {
                $record->status = 'pending';
            }

            $record->messages = $messages;
            $record->save();

            Notification::make()
                ->title('Reply sent to ticket #' . $record->id)
                ->success()
                ->send();

            // Clear the reply field
            unset($this->data['_reply_message']);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Don't try to save _reply_message to the model
        unset($data['_reply_message']);
        return $data;
    }
}
