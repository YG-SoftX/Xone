<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewEmailNotification extends Notification
{
    protected $payload;
    public function __construct($payload = []) { $this->payload = $payload; }
    public function via($notifiable) { return ["mail"]; }
}
