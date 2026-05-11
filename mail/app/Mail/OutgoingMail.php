<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment as MailAttachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutgoingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fromEmail,
        public string $subject,
        public string $body,
        public array $attachments = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->fromEmail,
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.outgoing',
        );
    }

    public function attachments(): array
    {
        return array_map(function ($attachment) {
            return MailAttachment::fromPath($attachment['path'])
                ->as($attachment['name'] ?? basename($attachment['path']))
                ->withMime($attachment['mime_type'] ?? 'application/octet-stream');
        }, $this->attachments);
    }
}
