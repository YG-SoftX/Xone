<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected Subscription $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $planNames = [
            'free' => 'Free Plan',
            'basic' => 'Basic Plan ($9.99/month)',
            'pro' => 'Pro Plan ($29.99/month)',
            'enterprise' => 'Enterprise Plan ($99.99/month)',
        ];

        return (new MailMessage)
            ->subject('Subscription Activated - YG Console')
            ->greeting('Hello ' . ($notifiable->name ?? 'Developer') . '!')
            ->line('Your subscription has been successfully activated.')
            ->line('Plan: ' . ($planNames[$this->subscription->plan_id] ?? $this->subscription->plan_id))
            ->line('Status: ' . ucfirst($this->subscription->status))
            ->line('Current Period: ' . $this->subscription->current_period_start->format('M d, Y') . ' - ' . $this->subscription->current_period_end->format('M d, Y'))
            ->action('Manage Subscription', url('/console/billing/subscriptions'))
            ->line('You can upgrade, downgrade, or cancel your subscription at any time.')
            ->salutation('Best regards, YG Console Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_id' => $this->subscription->plan_id,
            'status' => $this->subscription->status,
            'period_start' => $this->subscription->current_period_start->toISOString(),
            'period_end' => $this->subscription->current_period_end->toISOString(),
        ];
    }
}
