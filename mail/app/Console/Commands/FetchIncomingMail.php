<?php

namespace App\Console\Commands;

use App\Models\Mail;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchIncomingMail extends Command
{
    protected $signature = 'mail:fetch {--connection=imap}';
    protected $description = 'Fetch incoming emails via IMAP and store them in the database';

    /**
     * IMAP connection configuration.
     */
    protected function getConnectionConfig(): array
    {
        return [
            'host' => config('mail.incoming.host', 'mail.ygxone.com'),
            'port' => config('mail.incoming.port', 993),
            'encryption' => config('mail.incoming.encryption', 'ssl'),
            'username' => config('mail.incoming.username', config('mail.from.address')),
            'password' => config('mail.incoming.password', config('mail.mailers.smtp.password')),
        ];
    }

    public function handle(): void
    {
        $config = $this->getConnectionConfig();

        if (!$config['username'] || $config['username'] === 'hello@example.com') {
            $this->error('IMAP credentials not configured. Set MAIL_INCOMING_* in .env');
            return;
        }

        try {
            $mailbox = $this->connectImap($config);

            $this->info("Connected to IMAP server. Fetching emails...");

            $messages = imap_search($mailbox, 'UNSEEN');

            if (!$messages) {
                $this->info("No new emails to fetch.");
                imap_close($mailbox);
                return;
            }

            $processed = 0;
            $errors = 0;

            foreach ($messages as $msgNumber) {
                try {
                    $this->processMessage($mailbox, $msgNumber);
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to process email', [
                        'msg_number' => $msgNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            imap_close($mailbox, CL_EXPUNGE);

            $this->info("Processed {$processed} emails" . ($errors > 0 ? ", {$errors} errors" : ""));
        } catch (\Exception $e) {
            $this->error("IMAP connection failed: {$e->getMessage()}");
            Log::error('IMAP connection failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Connect to IMAP server.
     */
    protected function connectImap(array $config): \IMAP\Connection|bool
    {
        $mailboxPath = '{' . $config['host'] . ':' . $config['port'] . '/' . $config['encryption'] . '/novalidate-cert}INBOX';

        return imap_open(
            $mailboxPath,
            $config['username'],
            $config['password'],
            OP_READONLY,
            3
        );
    }

    /**
     * Process a single IMAP message.
     */
    protected function processMessage($mailbox, int $msgNumber): void
    {
        $header = imap_headerinfo($mailbox, $msgNumber);

        if (!$header) {
            return;
        }

        $from = $this->extractEmailFromAddress($header->from[0] ?? null);
        $to = $this->extractEmailFromAddress($header->to[0] ?? null);
        $subject = $header->subject ?? '(No Subject)';
        $date = $header->date ?? now()->toDateTimeString();

        // Find the user by the recipient email
        $user = User::where('email', $to)->first();

        if (!$user) {
            // Try to match by domain fallback or skip
            Log::info("No user found for incoming email to: {$to}");
            return;
        }

        // Get message body
        $body = $this->getBody($mailbox, $msgNumber);

        // Store in database
        $mail = Mail::create([
            'user_id' => $user->id,
            'from' => $from,
            'to' => $to,
            'subject' => $this->decodeMimeString($subject),
            'body' => $body,
            'folder' => 'inbox',
            'read' => false,
        ]);

        // Process attachments
        $this->processAttachments($mailbox, $msgNumber, $mail->id);

        Log::info("Email stored", [
            'mail_id' => $mail->id,
            'user_id' => $user->id,
            'from' => $from,
            'subject' => $subject,
        ]);
    }

    /**
     * Extract plain text/HTML body from message.
     */
    protected function getBody($mailbox, int $msgNumber): string
    {
        $structure = imap_fetchstructure($mailbox, $msgNumber);

        // Try HTML first, then plain text
        $body = $this->fetchPart($mailbox, $msgNumber, $structure, 'text/html')
            ?? $this->fetchPart($mailbox, $msgNumber, $structure, 'text/plain')
            ?? '(No body content)';

        // If HTML, strip tags for safety
        if ($this->fetchPart($mailbox, $msgNumber, $structure, 'text/html')) {
            $body = strip_tags($body, '<p><br><div><b><i><strong><em><a><ul><ol><li>');
        }

        return $body;
    }

    /**
     * Recursively fetch a part by MIME type.
     */
    protected function fetchPart($mailbox, int $msgNumber, $structure, string $mimeType, int $partNumber = null): ?string
    {
        if ($partNumber === null) {
            // Check if multipart
            if (isset($structure->parts) && count($structure->parts) > 0) {
                foreach ($structure->parts as $index => $part) {
                    $result = $this->fetchPart($mailbox, $msgNumber, $part, $mimeType, $index + 1);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }

            // Single part
            $type = $this->getMimeType($structure);
            if ($type === $mimeType) {
                $body = imap_fetchbody($mailbox, $msgNumber, 1);
                return $this->decodeBody($body, $structure->encoding);
            }
        } else {
            if (isset($structure->parts) && count($structure->parts) > 0) {
                foreach ($structure->parts as $index => $part) {
                    $subPartNum = "{$partNumber}." . ($index + 1);
                    $result = $this->fetchPart($mailbox, $msgNumber, $part, $mimeType, $subPartNum);
                    if ($result !== null) {
                        return $result;
                    }
                }
            }

            $type = $this->getMimeType($structure);
            if ($type === $mimeType) {
                $body = imap_fetchbody($mailbox, $msgNumber, $partNumber);
                return $this->decodeBody($body, $structure->encoding);
            }
        }

        return null;
    }

    /**
     * Get MIME subtype from structure.
     */
    protected function getMimeType($structure): string
    {
        $primaryType = $structure->type ?? 0;
        $subType = $structure->subtype ?? 'plain';

        $types = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
        $primary = $types[$primaryType] ?? 'TEXT';

        if ($primary === 'TEXT') {
            return 'text/' . strtolower($subType);
        }

        return strtolower($primary) . '/' . strtolower($subType);
    }

    /**
     * Decode body based on encoding.
     */
    protected function decodeBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            ENCBASE64 => base64_decode($body),
            ENCQUOTEDPRINTABLE => quoted_printable_decode($body),
            ENC8BIT, ENC7BIT, ENCBINARY => $body,
            default => $body,
        };
    }

    /**
     * Process and store attachments.
     */
    protected function processAttachments($mailbox, int $msgNumber, int $mailId): void
    {
        $structure = imap_fetchstructure($mailbox, $msgNumber);

        if (!isset($structure->parts)) {
            return;
        }

        foreach ($structure->parts as $index => $part) {
            // Only process non-text parts
            if (in_array($this->getMimeType($part), ['text/plain', 'text/html'], true)) {
                continue;
            }

            $partNum = $index + 1;
            $fileName = $this->getPartFileName($part, $partNum);
            $mimeType = $this->getMimeType($part);

            if (!$fileName) {
                $fileName = 'attachment_' . $partNum;
            }

            $data = imap_fetchbody($mailbox, $msgNumber, $partNum);
            $decodedData = $this->decodeBody($data, $part->encoding);
            $fileSize = strlen($decodedData);

            // Skip empty or tiny attachments
            if ($fileSize < 10) {
                continue;
            }

            // Store file
            $storagePath = "attachments/{$mailId}/" . Str::uuid() . '_' . $fileName;
            Storage::disk('local')->put($storagePath, $decodedData);

            Attachment::create([
                'mail_id' => $mailId,
                'file_name' => $fileName,
                'file_path' => $storagePath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ]);
        }
    }

    /**
     * Extract filename from part parameters.
     */
    protected function getPartFileName($part, int $partNum): ?string
    {
        // Check dparameters (RFC 2231)
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    return $this->decodeMimeString($param->value);
                }
            }
        }

        // Check parameters
        if (isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'name') {
                    return $this->decodeMimeString($param->value);
                }
            }
        }

        return null;
    }

    /**
     * Extract email address from address object.
     */
    protected function extractEmailFromAddress(?object $address): string
    {
        if (!$address) {
            return 'unknown@unknown.com';
        }

        if (isset($address->mailbox) && isset($address->host)) {
            return strtolower($address->mailbox . '@' . $address->host);
        }

        return 'unknown@unknown.com';
    }

    /**
     * Decode MIME-encoded string.
     */
    protected function decodeMimeString(string $string): string
    {
        $decoded = imap_utf8($string);
        return $decoded ?: $string;
    }
}
