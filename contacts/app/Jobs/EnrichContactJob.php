<?php

namespace App\Jobs;

use App\Models\Contact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class EnrichContactJob implements ShouldQueue
{
    use Queueable;

    protected $contactId;
    protected $email;
    protected $name;

    /**
     * Create a new job instance.
     */
    public function __construct(int $contactId, string $email, string $name = '')
    {
        $this->contactId = $contactId;
        $this->email = $email;
        $this->name = $name;
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Call YG Account AI Service for contact enrichment
            $ygAccountUrl = config('app.url', 'http://localhost:8000');
            $response = Http::timeout(5)->post($ygAccountUrl . '/api/ai/enrich-contact', [
                'email' => $this->email,
                'name' => $this->name
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Only update if confidence is high (>0.7)
                if (($data['confidence'] ?? 0) > 0.7) {
                    Contact::where('id', $this->contactId)->update([
                        'company' => $data['company'] ?? null,
                        'job_title' => $data['job_title'] ?? null,
                        'industry' => $data['industry'] ?? null,
                        'enriched_at' => now(),
                        'enrichment_source' => 'ai_inference'
                    ]);
                    
                    \Log::info('Contact enriched successfully', [
                        'contact_id' => $this->contactId,
                        'company' => $data['company'] ?? null,
                        'confidence' => $data['confidence'] ?? 0
                    ]);
                } else {
                    \Log::info('Contact enrichment skipped - low confidence', [
                        'contact_id' => $this->contactId,
                        'confidence' => $data['confidence'] ?? 0
                    ]);
                }
            } else {
                \Log::warning('Contact enrichment API failed', [
                    'contact_id' => $this->contactId,
                    'status' => $response->status()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Contact enrichment failed', [
                'contact_id' => $this->contactId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
