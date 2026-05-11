em<?php

namespace App\Jobs;

use App\Models\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class CategorizeEmailJob implements ShouldQueue
{
    use Queueable;

    protected $mailId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $mailId)
    {
        $this->mailId = $mailId;
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mail = Mail::find($this->mailId);

        if (!$mail) {
            return;
        }

        try {
            // Call YG Account AI Service
            $ygAccountUrl = config('app.url', 'http://localhost:8000');
            $response = Http::timeout(5)->post($ygAccountUrl . '/api/ai/categorize-email', [
                'subject' => $mail->subject ?? '',
                'body' => strip_tags($mail->body ?? ''),
                'from' => $mail->from ?? ''
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $mail->update([
                    'category' => $data['category'] ?? 'primary',
                    'priority' => $data['priority'] ?? 'normal'
                ]);

                \Log::info('Email categorized successfully', [
                    'mail_id' => $this->mailId,
                    'category' => $data['category'] ?? 'primary'
                ]);
            } else {
                \Log::warning('Email categorization API failed', [
                    'mail_id' => $this->mailId,
                    'status' => $response->status()
                ]);

                // Fallback to keyword-based categorization
                $this->fallbackCategorization($mail);
            }
        } catch (\Exception $e) {
            \Log::error('Email categorization failed', [
                'mail_id' => $this->mailId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback: keyword-based categorization
            $this->fallbackCategorization($mail);
        }
    }

    /**
     * Fallback categorization using keyword matching
     */
    protected function fallbackCategorization(Mail $mail): void
    {
        $text = strtolower(($mail->subject ?? '') . ' ' . strip_tags($mail->body ?? ''));

        // Promotions keywords
        if (preg_match('/offer|discount|sale|deal|promo|coupon|special offer|limited time/', $text)) {
            $mail->update(['category' => 'promotions']);
            return;
        }

        // Social keywords
        if (preg_match('/facebook|twitter|linkedin|instagram|friend request|connection request|social network/', $text)) {
            $mail->update(['category' => 'social']);
            return;
        }

        // Updates keywords
        if (preg_match('/receipt|confirmation|order|invoice|payment|shipping|delivery|tracking/', $text)) {
            $mail->update(['category' => 'updates']);
            return;
        }

        // Default to primary
        $mail->update(['category' => 'primary']);
    }
}
