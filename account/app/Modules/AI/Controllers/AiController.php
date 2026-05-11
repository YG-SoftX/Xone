<?php

namespace App\Modules\AI\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AiQuery;
use App\Services\EventService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display AI assistant dashboard
     */
    public function index()
    {
        $recentQueries = AiQuery::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('ai.index', compact('recentQueries'));
    }

    /**
     * Process AI query
     */
    public function query(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:5000',
            'service' => 'nullable|in:mail,drive,docs,meet,pay,general',
            'context' => 'nullable|json',
        ]);

        // Create query record
        $aiQuery = AiQuery::create([
            'user_id' => auth()->id(),
            'service' => $validated['service'] ?? 'general',
            'query' => $validated['query'],
            'metadata' => $validated['context'],
        ]);

        // Process with AI provider (simplified - in production integrate with OpenAI/Anthropic/etc.)
        $response = $this->processWithAI($validated['query'], $validated['service']);

        // Update query with response
        $aiQuery->update([
            'response' => $response,
            'model_used' => 'gpt-4',
            'tokens_used' => strlen($response) / 4, // Approximate
            'completed_at' => now(),
        ]);

        // Generate suggestions based on query
        $suggestions = $this->generateSuggestions($validated['query'], $validated['service']);

        return response()->json([
            'success' => true,
            'response' => $response,
            'suggestions' => $suggestions,
            'query_id' => $aiQuery->id,
        ]);
    }

    /**
     * Get smart reply suggestions for email
     */
    public function getSmartReplies(Request $request)
    {
        $validated = $request->validate([
            'email_content' => 'required|string',
            'email_from' => 'nullable|string',
        ]);

        // Generate smart replies using AI
        $replies = [
            'Thanks for your email! I\'ll get back to you soon.',
            'Thank you for reaching out. Let me review this and respond.',
            'I appreciate your message. I\'ll look into this right away.',
        ];

        // Save suggestion
        \App\Models\AiSuggestion::create([
            'user_id' => auth()->id(),
            'service' => 'mail',
            'suggestion_type' => 'smart_reply',
            'suggestion_data' => json_encode(['replies' => $replies]),
        ]);

        return response()->json(['replies' => $replies]);
    }

    /**
     * Categorize email using AI
     */
    public function categorizeEmail(Request $request)
    {
        $validated = $request->validate([
            'message_id' => 'required|exists:mail_messages,id',
        ]);

        $message = \App\Models\MailMessage::findOrFail($validated['message_id']);
        
        // Verify ownership
        abort_unless($message->mailbox->user_id === auth()->id(), 403);

        // AI categorization (simplified logic)
        $category = $this->categorizeEmailContent($message->subject, $message->body_plain);

        // Save categorization
        \App\Models\AiEmailCategory::create([
            'message_id' => $message->id,
            'category' => $category,
            'confidence_score' => 0.85,
        ]);

        return response()->json(['category' => $category]);
    }

    /**
     * Summarize document using AI
     */
    public function summarizeDocument(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required|exists:docs_documents,id',
        ]);

        $document = \App\Models\DocDocument::findOrFail($validated['document_id']);
        
        // Verify ownership or permission
        abort_unless($document->user_id === auth()->id(), 403);

        // Generate summary (simplified)
        $summary = substr($document->content, 0, 500) . '...';
        $keyPoints = ['Key point 1', 'Key point 2', 'Key point 3'];

        // Save summary
        \App\Models\AiDocumentSummary::create([
            'document_id' => $document->id,
            'summary' => $summary,
            'key_points' => json_encode($keyPoints),
            'word_count' => str_word_count($document->content),
        ]);

        return response()->json([
            'summary' => $summary,
            'key_points' => $keyPoints,
        ]);
    }

    /**
     * Process query with AI provider
     */
    protected function processWithAI(string $query, string $service): string
    {
        // In production, integrate with OpenAI, Anthropic, Google AI, etc.
        // For now, return mock response
        
        $responses = [
            'mail' => "I can help you manage your emails. You have several unread messages.",
            'drive' => "Your cloud storage is organized. I found 15 recent files.",
            'docs' => "I can assist with document editing and collaboration.",
            'meet' => "You have 2 upcoming meetings scheduled for today.",
            'pay' => "Your wallet balance is current. No pending transactions.",
            'general' => "How can I help you today? I'm your YG AI assistant.",
        ];

        return $responses[$service] ?? $responses['general'];
    }

    /**
     * Generate contextual suggestions
     */
    protected function generateSuggestions(string $query, string $service): array
    {
        return [
            'Check your recent emails',
            'Upload a new file to Drive',
            'Schedule a meeting',
            'View your wallet balance',
        ];
    }

    /**
     * Categorize email content
     */
    protected function categorizeEmailContent(string $subject, ?string $body): string
    {
        // Simple keyword-based categorization
        $lowerSubject = strtolower($subject);
        
        if (str_contains($lowerSubject, 'newsletter') || str_contains($lowerSubject, 'promo')) {
            return 'promotions';
        } elseif (str_contains($lowerSubject, 'facebook') || str_contains($lowerSubject, 'twitter')) {
            return 'social';
        } elseif (str_contains($lowerSubject, 'invoice') || str_contains($lowerSubject, 'receipt')) {
            return 'updates';
        }
        
        return 'primary';
    }
}
