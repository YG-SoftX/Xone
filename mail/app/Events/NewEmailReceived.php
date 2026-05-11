<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewEmailReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mailboxId;
    public $emailData;

    /**
     * Create a new event instance.
     */
    public function __construct(int $mailboxId, array $emailData)
    {
        $this->mailboxId = $mailboxId;
        $this->emailData = $emailData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("mailbox.{$this->mailboxId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'email.received';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->emailData['id'],
            'from' => $this->emailData['from_email'],
            'from_name' => $this->emailData['from_name'],
            'subject' => $this->emailData['subject'],
            'preview' => $this->emailData['preview'],
            'received_at' => $this->emailData['received_at'],
            'has_attachments' => $this->emailData['has_attachments'] ?? false,
            'is_important' => $this->emailData['is_important'] ?? false,
        ];
    }
}
