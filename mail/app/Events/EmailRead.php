<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailRead
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $messageId;
    public $mailboxId;
    public function __construct($messageId, $mailboxId) {
        $this->messageId = $messageId;
        $this->mailboxId = $mailboxId;
    }
}
