<?php

namespace App\Jobs;

use App\Models\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class AnalyzeEmailSentimentJob implements ShouldQueue
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
            $response = Http::timeout(5)->post($ygAccountUrl . '/api/ai/analyze-sentiment', [
                'text' => strip_tags($mail->body ?? '')
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $sentimentScore = $data['score'] ?? 0;
                $sentimentLabel = $data['label'] ?? 'neutral';
                $confidence = $data['confidence'] ?? 0;
                
                // Determine priority based on sentiment
                $priority = 'normal';
                if ($sentimentScore < -0.5) {
                    $priority = 'high'; // Negative emails need attention
                } elseif ($sentimentScore > 0.7 && $confidence > 0.8) {
                    $priority = 'low'; // Very positive, less urgent
                }
                
                $mail->update([
                    'sentiment_score' => $sentimentScore,
                    'sentiment_label' => $sentimentLabel,
                    'sentiment_confidence' => $confidence,
                    'priority' => $priority
                ]);
                
                \Log::info('Email sentiment analyzed', [
                    'mail_id' => $this->mailId,
                    'sentiment' => $sentimentLabel,
                    'score' => $sentimentScore,
                    'priority' => $priority
                ]);
            } else {
                \Log::warning('Sentiment analysis API failed', [
                    'mail_id' => $this->mailId,
                    'status' => $response->status()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Sentiment analysis failed', [
                'mail_id' => $this->mailId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
