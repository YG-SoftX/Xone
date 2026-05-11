<?php

namespace App\Notifications;

use App\Models\BillingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    protected BillingInvoice $invoice;

    public function __construct(BillingInvoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invoice #' . $this->invoice->invoice_number . ' - YG Console')
            ->greeting('Hello ' . ($notifiable->name ?? 'Developer') . '!')
            ->line('Your invoice for $' . number_format($this->invoice->amount, 2) . ' has been generated.')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount Due: $' . number_format($this->invoice->amount, 2))
            ->line('Due Date: ' . $this->invoice->due_date->format('F d, Y'))
            ->action('View Invoice', url('/console/invoices/' . $this->invoice->id))
            ->line('If you have any questions, please contact our support team.')
            ->salutation('Best regards, YG Console Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->amount,
            'currency' => $this->invoice->currency,
            'due_date' => $this->invoice->due_date->toISOString(),
        ];
    }
}
