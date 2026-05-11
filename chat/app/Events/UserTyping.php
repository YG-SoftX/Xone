<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $spaceId;
    public array $user;

    public function __construct(int $spaceId, array $user)
    {
        $this->spaceId = $spaceId;
        $this->user    = $user;
    }

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('chat-space.' . $this->spaceId);
    }

    public function broadcastWith(): array
    {
        return ['user' => $this->user, 'space_id' => $this->spaceId];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }
}
