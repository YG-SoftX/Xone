<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message->load('sender');
    }

    /**
     * Broadcast on the private chat space channel
     */
    public function broadcastOn(): Channel
    {
        return new PresenceChannel('chat-space.' . $this->message->space_id);
    }

    /**
     * Data sent to the frontend via WebSocket
     */
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'body'       => $this->message->body,
            'type'       => $this->message->type,
            'space_id'   => $this->message->space_id,
            'sender'     => [
                'id'     => $this->message->sender?->id,
                'name'   => $this->message->sender?->name,
                'avatar' => $this->message->sender?->avatar ?? null,
            ],
            'sent_at'    => $this->message->created_at->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
