<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YGAIService
{
    protected $baseUrl;
    protected $timeout = 5;
    protected $cacheTtl = 3600; // 1 hour default cache

    public function __construct()
    {
        $this->baseUrl = config('services.yg_ai.url', 'https://ai.ygxone.com');
    }

    /**
     * Generate smart reply suggestions for an email
     * 
     * @param string $emailBody The email content
     * @param string $senderName Sender's name
     * @param int $count Number of suggestions (default 3)
     * @return array Array of reply suggestions
     */
    public function generateSmartReply(string $emailBody, string $senderName = '', int $count = 3): array
    {
        try {
            $cacheKey = "ai_smart_reply_" . md5($emailBody . $count);
            
            return Cache::remember($cacheKey, $this->cacheTtl, function () use ($emailBody, $senderName, $count) {
                $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                    'action' => 'brain_chat',
                    'model' => 'default',
                    'context' => "Email from {$senderName}",
                    'prompt' => "Generate {$count} short, professional reply suggestions (max 2 sentences each) to this email. Format as JSON array with 'suggestions' key:\n\n{$emailBody}",
                    'format' => 'json'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['suggestions'] ?? $this->extractSuggestionsFromText($data['answer'] ?? '');
                }

                Log::warning('YG AI smart reply failed', ['status' => $response->status()]);
                return $this->getDefaultReplies();
            });
        } catch (\Exception $e) {
            Log::error('YG AI smart reply exception', ['error' => $e->getMessage()]);
            return $this->getDefaultReplies();
        }
    }

    /**
     * Categorize an email into Primary/Social/Promotions/Updates
     * 
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string $from Sender email address
     * @return string Category: 'primary', 'social', 'promotions', or 'updates'
     */
    public function categorizeEmail(string $subject, string $body, string $from = ''): string
    {
        try {
            $cacheKey = "ai_email_cat_" . md5($subject . $from);
            
            return Cache::remember($cacheKey, $this->cacheTtl * 24, function () use ($subject, $body, $from) {
                $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                    'action' => 'brain_chat',
                    'model' => 'default',
                    'context' => 'Email categorization task',
                    'prompt' => "Categorize this email into ONE of these categories: primary, social, promotions, updates.\n\n" .
                               "Rules:\n" .
                               "- primary: Personal emails, important work communications\n" .
                               "- social: Social media notifications, networking\n" .
                               "- promotions: Marketing, offers, newsletters\n" .
                               "- updates: Receipts, confirmations, account notifications\n\n" .
                               "From: {$from}\n" .
                               "Subject: {$subject}\n" .
                               "Body preview: " . substr(strip_tags($body), 0, 500) . "\n\n" .
                               "Respond with ONLY the category name (lowercase).",
                    'format' => 'text'
                ]);

                if ($response->successful()) {
                    $category = trim(strtolower($response->body()));
                    
                    // Validate category
                    if (in_array($category, ['primary', 'social', 'promotions', 'updates'])) {
                        return $category;
                    }
                }

                // Fallback: simple keyword-based categorization
                return $this->fallbackCategorization($subject, $body, $from);
            });
        } catch (\Exception $e) {
            Log::error('YG AI email categorization exception', ['error' => $e->getMessage()]);
            return $this->fallbackCategorization($subject, $body, $from);
        }
    }

    /**
     * Generate writing suggestions for a document
     * 
     * @param string $text Text to analyze
     * @param int $position Cursor position in text
     * @return array Suggestions with type, message, and suggested fix
     */
    public function suggestDocumentEdits(string $text, int $position = 0): array
    {
        try {
            $contextWindow = 200; // characters around cursor
            $start = max(0, $position - $contextWindow);
            $end = min(strlen($text), $position + $contextWindow);
            $context = substr($text, $start, $end - $start);

            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                'action' => 'brain_chat',
                'model' => 'default',
                'context' => 'Document editing assistant',
                'prompt' => "Review this text and provide up to 3 writing improvement suggestions (grammar, clarity, style). " .
                           "Format as JSON array with objects containing 'type' (grammar/clarity/style), 'message', and 'suggestion':\n\n{$context}",
                'format' => 'json'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['suggestions'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('YG AI document suggestions exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Parse natural language into calendar event
     * 
     * @param string $input Natural language input (e.g., "Meeting with John tomorrow at 3pm")
     * @param int $userId Current user ID for timezone
     * @return array Parsed event data with title, start_time, end_time, attendees
     */
    public function parseCalendarEvent(string $input, int $userId): array
    {
        try {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                'action' => 'brain_chat',
                'model' => 'default',
                'context' => 'Calendar event parser',
                'prompt' => "Parse this natural language into a calendar event. Extract: title, date/time (ISO 8601 format), duration (minutes), attendees (email addresses).\n\n" .
                           "Input: {$input}\n\n" .
                           "Respond with JSON: {\"title\": \"...\", \"start_time\": \"YYYY-MM-DDTHH:mm:ss\", \"duration_minutes\": 60, \"attendees\": [\"email@example.com\"]}",
                'format' => 'json'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => 'Failed to parse event'];
        } catch (\Exception $e) {
            Log::error('YG AI calendar parsing exception', ['error' => $e->getMessage()]);
            return ['error' => 'Parsing failed'];
        }
    }

    /**
     * Enrich contact with company/social data
     * 
     * @param string $email Contact email
     * @param string $name Contact name (optional)
     * @return array Enriched data with company, job_title, social_profiles
     */
    public function enrichContact(string $email, string $name = ''): array
    {
        try {
            $cacheKey = "ai_contact_enrich_" . md5($email);
            
            return Cache::remember($cacheKey, $this->cacheTtl * 7, function () use ($email, $name) {
                // Extract domain from email
                $domain = substr(strrchr($email, "@"), 1);
                
                $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                    'action' => 'brain_chat',
                    'model' => 'default',
                    'context' => 'Contact enrichment',
                    'prompt' => "Based on email {$email}" . ($name ? " and name {$name}" : "") . ", provide likely company information. " .
                               "Domain is {$domain}. Return JSON with: company_name, industry, job_title_guess, confidence_score (0-1).\n" .
                               "If uncertain, set confidence_score low.",
                    'format' => 'json'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'company' => $data['company_name'] ?? null,
                        'industry' => $data['industry'] ?? null,
                        'job_title' => $data['job_title_guess'] ?? null,
                        'confidence' => $data['confidence_score'] ?? 0,
                        'source' => 'ai_inference'
                    ];
                }

                return ['source' => 'none', 'confidence' => 0];
            });
        } catch (\Exception $e) {
            Log::error('YG AI contact enrichment exception', ['error' => $e->getMessage()]);
            return ['source' => 'error', 'confidence' => 0];
        }
    }

    /**
     * Summarize long text
     * 
     * @param string $text Text to summarize
     * @param int $maxLength Maximum summary length (words)
     * @return string Summary
     */
    public function summarizeText(string $text, int $maxLength = 100): string
    {
        try {
            $cacheKey = "ai_summary_" . md5($text . $maxLength);
            
            return Cache::remember($cacheKey, $this->cacheTtl, function () use ($text, $maxLength) {
                $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                    'action' => 'brain_chat',
                    'model' => 'default',
                    'context' => 'Text summarization',
                    'prompt' => "Summarize the following text in maximum {$maxLength} words. Focus on key points:\n\n" . substr($text, 0, 3000),
                    'format' => 'text'
                ]);

                if ($response->successful()) {
                    return trim($response->body());
                }

                // Fallback: extract first few sentences
                return $this->fallbackSummary($text, $maxLength);
            });
        } catch (\Exception $e) {
            Log::error('YG AI summarization exception', ['error' => $e->getMessage()]);
            return $this->fallbackSummary($text, $maxLength);
        }
    }

    /**
     * Analyze sentiment of text
     * 
     * @param string $text Text to analyze
     * @return array Sentiment analysis with score (-1 to 1) and label
     */
    public function analyzeSentiment(string $text): array
    {
        try {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                'action' => 'brain_chat',
                'model' => 'default',
                'context' => 'Sentiment analysis',
                'prompt' => "Analyze sentiment of this text. Return JSON with: score (-1=negative, 0=neutral, 1=positive), label (negative/neutral/positive), confidence (0-1):\n\n" . substr($text, 0, 1000),
                'format' => 'json'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['score' => 0, 'label' => 'neutral', 'confidence' => 0];
        } catch (\Exception $e) {
            Log::error('YG AI sentiment analysis exception', ['error' => $e->getMessage()]);
            return ['score' => 0, 'label' => 'neutral', 'confidence' => 0];
        }
    }

    /**
     * Extract named entities from text
     * 
     * @param string $text Text to analyze
     * @return array Entities with type (PERSON, ORG, LOCATION, DATE, etc.)
     */
    public function extractEntities(string $text): array
    {
        try {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                'action' => 'brain_chat',
                'model' => 'default',
                'context' => 'Named entity recognition',
                'prompt' => "Extract named entities from this text. Return JSON array of objects with 'text', 'type' (PERSON/ORG/LOCATION/DATE/MONEY/PRODUCT), and 'confidence':\n\n" . substr($text, 0, 2000),
                'format' => 'json'
            ]);

            if ($response->successful()) {
                return $response->json()['entities'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('YG AI entity extraction exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Describe image content (multimodal)
     * 
     * @param string $imageUrl URL of image to analyze
     * @return string Description of image
     */
    public function describeImage(string $imageUrl): string
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/api/", [
                'action' => 'describe_image',
                'model' => 'default',
                'image_url' => $imageUrl,
                'prompt' => 'Describe this image in detail.',
                'format' => 'text'
            ]);

            if ($response->successful()) {
                return trim($response->body());
            }

            return 'Unable to analyze image';
        } catch (\Exception $e) {
            Log::error('YG AI image description exception', ['error' => $e->getMessage()]);
            return 'Image analysis unavailable';
        }
    }

    /**
     * Translate text to target language
     * 
     * @param string $text Text to translate
     * @param string $targetLang Target language code (e.g., 'ne' for Nepali, 'es' for Spanish)
     * @return string Translated text
     */
    public function translateText(string $text, string $targetLang = 'en'): string
    {
        try {
            $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/", [
                'action' => 'brain_chat',
                'model' => 'default',
                'context' => "Translation to {$targetLang}",
                'prompt' => "Translate the following text to {$targetLang}. Return only the translation:\n\n{$text}",
                'format' => 'text'
            ]);

            if ($response->successful()) {
                return trim($response->body());
            }

            return $text; // Return original if translation fails
        } catch (\Exception $e) {
            Log::error('YG AI translation exception', ['error' => $e->getMessage()]);
            return $text;
        }
    }

    // ========== HELPER METHODS ==========

    /**
     * Extract suggestions from plain text response
     */
    private function extractSuggestionsFromText(string $text): array
    {
        // Try to parse numbered list
        preg_match_all('/\d+\.\s*(.+?)(?=\n\d+\.|\n*$)/s', $text, $matches);
        
        if (!empty($matches[1])) {
            return array_map('trim', $matches[1]);
        }

        // Fallback: split by newlines
        return array_filter(array_map('trim', explode("\n", $text)));
    }

    /**
     * Get default reply suggestions when AI fails
     */
    private function getDefaultReplies(): array
    {
        return [
            'Thank you for your email. I will review and get back to you soon.',
            'Thanks for reaching out! Let me check on this and respond shortly.',
            'I appreciate your message. I will follow up with you shortly.'
        ];
    }

    /**
     * Fallback email categorization using keywords
     */
    private function fallbackCategorization(string $subject, string $body, string $from): string
    {
        $text = strtolower($subject . ' ' . $body);
        
        // Promotions keywords
        $promoKeywords = ['offer', 'discount', 'sale', 'deal', 'promo', 'coupon', 'buy now', 'limited time', 'subscribe'];
        foreach ($promoKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'promotions';
            }
        }

        // Social keywords
        $socialKeywords = ['facebook', 'twitter', 'linkedin', 'instagram', 'friend request', 'connection', 'follow'];
        foreach ($socialKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'social';
            }
        }

        // Updates keywords
        $updateKeywords = ['receipt', 'confirmation', 'order', 'invoice', 'payment', 'shipping', 'delivered'];
        foreach ($updateKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'updates';
            }
        }

        // Default to primary
        return 'primary';
    }

    /**
     * Fallback text summary using extractive method
     */
    private function fallbackSummary(string $text, int $maxLength): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($sentences)) {
            return substr($text, 0, $maxLength * 10);
        }

        // Take first 2-3 sentences
        $summary = implode(' ', array_slice($sentences, 0, 3));
        
        // Truncate to maxLength words
        $words = explode(' ', $summary);
        if (count($words) > $maxLength) {
            $summary = implode(' ', array_slice($words, 0, $maxLength)) . '...';
        }

        return $summary;
    }
}
