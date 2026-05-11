<?php

namespace App\Jobs;

use App\Mail\OutgoingMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;
    public $timeout = 120;

    public function __construct(
        public string $to,
        public string $subject,
        public string $body,
        public string $fromEmail,
        public array $attachments = [],
        public ?int $mailRecordId = null,
    ) {}

    public function handle(): void
    {
        $mail = new OutgoingMail(
            fromEmail: $this->fromEmail,
            subject: $this->subject,
            body: $this->body,
            attachments: $this->attachments,
        );

        Mail::to($this->to)->send($mail);
    }

    public function failed(\Throwable $exception): void
    {
        // Optionally update the mail record to mark as failed
        if ($this->mailRecordId) {
            $mailModel = \App\Models\Mail::find($this->mailRecordId);
            if ($mailModel) {
                $mailModel->update([
                    'folder' => 'failed',
                ]);
            }
        }

        \Illuminate\Support\Facades\Log::error('Email sending failed', [
            'to' => $this->to,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
