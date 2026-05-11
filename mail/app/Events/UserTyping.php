<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $mailboxId;
    public $recipientId;
    public function __construct($mailboxId, $recipientId) {
        $this->mailboxId = $mailboxId;
        $this->recipientId = $recipientId;
    }
}
