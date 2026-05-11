<?php

namespace App\Mail;

use App\Models\CronJob;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CronJobFailedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public CronJob $cronJob;
    public string $errorMessage;
    public int $consecutiveFailures;

    /**
     * Create a new message instance.
     */
    public function __construct(CronJob $cronJob, string $errorMessage, int $consecutiveFailures = 1)
    {
        $this->cronJob = $cronJob;
        $this->errorMessage = $errorMessage;
        $this->consecutiveFailures = $consecutiveFailures;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject("🚨 Cron Job Failed: {$this->cronJob->name}")
                    ->markdown('emails.cron-job-failed')
                    ->with([
                        'job' => $this->cronJob,
                        'error' => $this->errorMessage,
                        'failures' => $this->consecutiveFailures,
                        'adminUrl' => url('/admin/cron-jobs'),
                    ]);
    }
}
