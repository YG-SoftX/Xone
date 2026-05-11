<?php

namespace App\Notifications;

use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WebhookDeliveryFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected WebhookEndpoint $endpoint;
    protected string $eventType;
    protected int $attempt;

    public function __construct(WebhookEndpoint $endpoint, string $eventType, int $attempt)
    {
        $this->endpoint = $endpoint;
        $this->eventType = $eventType;
        $this->attempt = $attempt;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Webhook Delivery Failed - YG Console')
            ->greeting('Hello ' . ($notifiable->name ?? 'Developer') . '!')
            ->line('A webhook delivery has failed after ' . $this->attempt . ' attempt(s).')
            ->line('Event Type: ' . $this->eventType)
            ->line('Endpoint URL: ' . $this->endpoint->url)
            ->line('Project: ' . $this->endpoint->project->name)
            ->action('View Webhook Settings', url('/console/webhooks/' . $this->endpoint->id))
            ->line('Please check your endpoint configuration and ensure it\'s accessible.')
            ->salutation('Best regards, YG Console Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'endpoint_id' => $this->endpoint->id,
            'event_type' => $this->eventType,
            'attempt' => $this->attempt,
            'url' => $this->endpoint->url,
            'project_name' => $this->endpoint->project->name,
        ];
    }
}
