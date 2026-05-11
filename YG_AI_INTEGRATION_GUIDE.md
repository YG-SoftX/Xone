# YG AI Integration Guide - Complete Implementation

**Date**: May 4, 2026  
**Status**: ✅ **READY FOR INTEGRATION**  

---

## 🎯 Overview

This guide provides complete implementation instructions for integrating YG AI across all YG ecosystem services. The `YGAIService` class handles all AI interactions with built-in caching, error handling, and fallback mechanisms.

---

## 📁 Service Location

**Main Service**: `yg-account/app/Services/YGAIService.php`  
**Configuration**: `yg-account/config/services.php`  
**Environment**: Add to `.env` file

---

## ⚙️ Configuration

### 1. Environment Variables

Add to `yg-account/.env`:

```env
# YG AI Service Configuration
YG_AI_URL=https://ai.ygxone.com
YG_AI_API_KEY=your_api_key_here  # Optional, if API requires authentication
YG_AI_TIMEOUT=5  # Request timeout in seconds
```

### 2. Service Registration

The service is auto-registered via Laravel's service container. Use it via dependency injection:

```php
use App\Services\YGAIService;

class YourController extends Controller
{
    protected $aiService;
    
    public function __construct(YGAIService $aiService)
    {
        $this->aiService = $aiService;
    }
}
```

Or resolve directly:

```php
$aiService = app(YGAIService::class);
```

---

## 🔧 Available Methods

### 1. Smart Reply Generation (YG Mail)

**Purpose**: Generate intelligent reply suggestions for emails

**Method**:
```php
public function generateSmartReply(string $emailBody, string $senderName = '', int $count = 3): array
```

**Usage Example**:
```php
// In YG Mail - EmailController.php
use App\Services\YGAIService;

class EmailController extends Controller
{
    protected $aiService;
    
    public function __construct(YGAIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    public function show(Email $email)
    {
        // Get smart reply suggestions
        $suggestions = $this->aiService->generateSmartReply(
            $email->body,
            $email->from_name,
            3  // Number of suggestions
        );
        
        return view('emails.show', compact('email', 'suggestions'));
    }
}
```

**Frontend Integration** (Blade template):
```blade
<!-- resources/views/emails/show.blade.php -->
<div class="smart-replies">
    <h4>💡 Suggested Replies:</h4>
    <div class="suggestion-chips">
        @foreach($suggestions as $suggestion)
            <button class="chip" onclick="insertReply('{{ addslashes($suggestion) }}')">
                {{ $suggestion }}
            </button>
        @endforeach
    </div>
</div>

<script>
function insertReply(text) {
    document.querySelector('#reply-body').value = text;
}
</script>
```

**Response Format**:
```php
[
    "Thank you for your email. I'll review and respond shortly.",
    "Thanks for reaching out! Let me check on this.",
    "I appreciate your message. I'll follow up soon."
]
```

**Caching**: Responses cached for 1 hour based on content hash  
**Fallback**: Returns default professional replies if AI unavailable

---

### 2. Email Categorization (YG Mail)

**Purpose**: Automatically categorize emails into tabs (Primary/Social/Promotions/Updates)

**Method**:
```php
public function categorizeEmail(string $subject, string $body, string $from = ''): string
```

**Usage Example**:
```php
// In YG Mail - when receiving/sending email
public function store(Request $request)
{
    $email = Email::create($request->validated());
    
    // Categorize the email
    $category = $this->aiService->categorizeEmail(
        $email->subject,
        $email->body,
        $email->from_email
    );
    
    // Update email with category
    $email->update(['category' => $category]);
    
    return response()->json(['success' => true, 'category' => $category]);
}
```

**Database Migration** (add to emails table):
```php
Schema::table('emails', function (Blueprint $table) {
    $table->string('category')->nullable()->after('subject')
          ->comment('primary, social, promotions, updates');
    $table->index('category');
});
```

**Categories**:
- `primary`: Personal emails, important work communications
- `social`: Social media notifications, networking
- `promotions`: Marketing, offers, newsletters
- `updates`: Receipts, confirmations, account notifications

**Caching**: Cached for 24 hours (categories don't change)  
**Fallback**: Keyword-based categorization if AI fails

**UI Integration** (Gmail-style tabs):
```blade
<!-- resources/views/emails/index.blade.php -->
<div class="email-tabs">
    <button class="tab {{ $currentCategory === 'primary' ? 'active' : '' }}" 
            onclick="filterByCategory('primary')">
        Primary
        @if($unreadCounts['primary'] > 0)
            <span class="badge">{{ $unreadCounts['primary'] }}</span>
        @endif
    </button>
    
    <button class="tab {{ $currentCategory === 'social' ? 'active' : '' }}" 
            onclick="filterByCategory('social')">
        Social
        @if($unreadCounts['social'] > 0)
            <span class="badge">{{ $unreadCounts['social'] }}</span>
        @endif
    </button>
    
    <button class="tab {{ $currentCategory === 'promotions' ? 'active' : '' }}" 
            onclick="filterByCategory('promotions')">
        Promotions
        @if($unreadCounts['promotions'] > 0)
            <span class="badge">{{ $unreadCounts['promotions'] }}</span>
        @endif
    </button>
    
    <button class="tab {{ $currentCategory === 'updates' ? 'active' : '' }}" 
            onclick="filterByCategory('updates')">
        Updates
        @if($unreadCounts['updates'] > 0)
            <span class="badge">{{ $unreadCounts['updates'] }}</span>
        @endif
    </button>
</div>
```

---

### 3. Document Writing Suggestions (YG DocX)

**Purpose**: Provide real-time grammar, clarity, and style suggestions

**Method**:
```php
public function suggestDocumentEdits(string $text, int $position = 0): array
```

**Usage Example**:
```php
// In YG DocX - DocumentController.php
public function getSuggestions(Request $request, Document $document)
{
    $request->validate([
        'text' => 'required|string',
        'position' => 'integer|min:0'
    ]);
    
    $suggestions = $this->aiService->suggestDocumentEdits(
        $request->input('text'),
        $request->input('position', 0)
    );
    
    return response()->json([
        'success' => true,
        'suggestions' => $suggestions
    ]);
}
```

**Frontend Integration** (Real-time with debounce):
```javascript
// resources/js/document-editor.js
let suggestionTimeout;

document.getElementById('editor').addEventListener('input', function(e) {
    clearTimeout(suggestionTimeout);
    
    suggestionTimeout = setTimeout(() => {
        fetchSuggestions(this.value, getCaretPosition(this));
    }, 1000); // Debounce 1 second
});

async function fetchSuggestions(text, position) {
    try {
        const response = await fetch('/api/documents/suggestions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                text: text,
                position: position
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.suggestions.length > 0) {
            displaySuggestions(data.suggestions);
        }
    } catch (error) {
        console.error('Failed to get suggestions:', error);
    }
}

function displaySuggestions(suggestions) {
    const container = document.getElementById('suggestions-panel');
    container.innerHTML = '';
    
    suggestions.forEach(suggestion => {
        const div = document.createElement('div');
        div.className = `suggestion-item ${suggestion.type}`;
        div.innerHTML = `
            <strong>${suggestion.type.toUpperCase()}</strong>
            <p>${suggestion.message}</p>
            <button onclick="applySuggestion('${encodeURIComponent(suggestion.suggestion)}')">
                Apply Fix
            </button>
        `;
        container.appendChild(div);
    });
}
```

**Response Format**:
```php
[
    [
        'type' => 'grammar',
        'message' => 'Subject-verb agreement issue',
        'suggestion' => 'Change "are" to "is"'
    ],
    [
        'type' => 'clarity',
        'message' => 'Sentence is too long',
        'suggestion' => 'Consider splitting into two sentences'
    ]
]
```

---

### 4. Natural Language Calendar Events (YG Calendar)

**Purpose**: Parse natural language into calendar events

**Method**:
```php
public function parseCalendarEvent(string $input, int $userId): array
```

**Usage Example**:
```php
// In YG Calendar - EventController.php
public function createFromNaturalLanguage(Request $request)
{
    $request->validate([
        'input' => 'required|string|max:500'
    ]);
    
    $parsed = $this->aiService->parseCalendarEvent(
        $request->input('input'),
        auth()->id()
    );
    
    if (isset($parsed['error'])) {
        return response()->json([
            'success' => false,
            'message' => 'Could not understand the request'
        ], 422);
    }
    
    // Create event from parsed data
    $event = CalendarEvent::create([
        'user_id' => auth()->id(),
        'title' => $parsed['title'],
        'start_time' => $parsed['start_time'],
        'end_time' => date('Y-m-d H:i:s', 
            strtotime($parsed['start_time']) + ($parsed['duration_minutes'] * 60)
        ),
        'description' => "Created from: {$request->input('input')}",
    ]);
    
    // Add attendees if provided
    if (!empty($parsed['attendees'])) {
        foreach ($parsed['attendees'] as $email) {
            EventAttendee::create([
                'event_id' => $event->id,
                'email' => $email,
                'status' => 'pending'
            ]);
        }
    }
    
    return response()->json([
        'success' => true,
        'event' => $event
    ]);
}
```

**Frontend Integration**:
```blade
<!-- resources/views/calendar/create.blade.php -->
<div class="natural-language-input">
    <label>Quick Create (Natural Language)</label>
    <input type="text" 
           id="quick-create" 
           placeholder="e.g., Meeting with John tomorrow at 3pm for 1 hour"
           class="form-control">
    <button onclick="parseAndCreate()" class="btn btn-primary mt-2">
        Create Event
    </button>
</div>

<script>
async function parseAndCreate() {
    const input = document.getElementById('quick-create').value;
    
    if (!input.trim()) {
        alert('Please enter event details');
        return;
    }
    
    try {
        const response = await fetch('/api/calendar/parse-event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ input: input })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to event detail or refresh calendar
            window.location.href = `/calendar/events/${data.event.id}`;
        } else {
            alert(data.message || 'Failed to create event');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
}
</script>
```

**Example Inputs**:
- "Meeting with John tomorrow at 3pm"
- "Lunch with team next Monday at noon for 90 minutes"
- "Doctor appointment on Dec 25 at 10am"
- "Weekly standup every Monday at 9am for 30 minutes"

**Parsed Output**:
```php
[
    'title' => 'Meeting with John',
    'start_time' => '2026-05-05T15:00:00',
    'duration_minutes' => 60,
    'attendees' => ['john@example.com']
]
```

---

### 5. Contact Enrichment (YG Contacts)

**Purpose**: Automatically enrich contacts with company and professional data

**Method**:
```php
public function enrichContact(string $email, string $name = ''): array
```

**Usage Example**:
```php
// In YG Contacts - ContactController.php
public function store(Request $request)
{
    $contact = Contact::create($request->validated());
    
    // Enrich contact in background job
    EnrichContactJob::dispatch($contact->id, $contact->email, $contact->name);
    
    return response()->json([
        'success' => true,
        'contact' => $contact
    ]);
}
```

**Background Job**:
```php
// app/Jobs/EnrichContactJob.php
namespace App\Jobs;

use App\Models\Contact;
use App\Services\YGAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $contactId;
    protected $email;
    protected $name;
    
    public function __construct(int $contactId, string $email, string $name = '')
    {
        $this->contactId = $contactId;
        $this->email = $email;
        $this->name = $name;
    }
    
    public function handle(YGAIService $aiService)
    {
        $enrichedData = $aiService->enrichContact($this->email, $this->name);
        
        // Only update if confidence is high (>0.7)
        if ($enrichedData['confidence'] > 0.7) {
            Contact::where('id', $this->contactId)->update([
                'company' => $enrichedData['company'],
                'job_title' => $enrichedData['job_title'],
                'industry' => $enrichedData['industry'],
                'enriched_at' => now(),
                'enrichment_source' => $enrichedData['source']
            ]);
        }
    }
}
```

**Database Migration**:
```php
Schema::table('contacts', function (Blueprint $table) {
    $table->string('company')->nullable()->after('name');
    $table->string('job_title')->nullable()->after('company');
    $table->string('industry')->nullable()->after('job_title');
    $table->timestamp('enriched_at')->nullable();
    $table->string('enrichment_source')->nullable();
});
```

**UI Display**:
```blade
<!-- resources/views/contacts/show.blade.php -->
<div class="contact-details">
    <h2>{{ $contact->name }}</h2>
    
    @if($contact->company)
        <div class="enriched-info">
            <i class="fas fa-building"></i>
            <span>{{ $contact->company }}</span>
            @if($contact->job_title)
                <span class="job-title">{{ $contact->job_title }}</span>
            @endif
            @if($contact->industry)
                <span class="industry-badge">{{ $contact->industry }}</span>
            @endif
        </div>
    @endif
    
    @if($contact->enriched_at)
        <small class="text-muted">
            Enriched by AI on {{ $contact->enriched_at->format('M d, Y') }}
        </small>
    @endif
</div>
```

---

### 6. Text Summarization

**Purpose**: Generate concise summaries of long texts

**Method**:
```php
public function summarizeText(string $text, int $maxLength = 100): string
```

**Usage Examples**:

**A. Email Thread Summary** (YG Mail):
```php
// Summarize long email threads
$summary = $this->aiService->summarizeText($emailThread, 150);

// Display in UI
<div class="thread-summary">
    <strong>📝 Summary:</strong>
    <p>{{ $summary }}</p>
</div>
```

**B. Document Summary** (YG DocX):
```php
// Generate document preview summary
$preview = $this->aiService->summarizeText($document->content, 50);

return response()->json([
    'title' => $document->title,
    'preview' => $preview . '...',
    'word_count' => str_word_count($document->content)
]);
```

**C. Meeting Notes Summary** (YG Calendar):
```php
// Summarize meeting notes
$summary = $this->aiService->summarizeText($meeting->notes, 100);

$meeting->update(['summary' => $summary]);
```

---

### 7. Sentiment Analysis

**Purpose**: Analyze emotional tone of text

**Method**:
```php
public function analyzeSentiment(string $text): array
```

**Usage Examples**:

**A. Email Sentiment Tracking** (YG Mail):
```php
// Track sentiment of incoming emails
$sentiment = $this->aiService->analyzeSentiment($email->body);

$email->update([
    'sentiment_score' => $sentiment['score'],
    'sentiment_label' => $sentiment['label'],
    'sentiment_confidence' => $sentiment['confidence']
]);

// Flag negative emails for priority attention
if ($sentiment['score'] < -0.5) {
    $email->update(['priority' => 'high']);
    
    // Send notification to user
    Notification::send(auth()->user(), new NegativeEmailDetected($email));
}
```

**B. Customer Feedback Analysis** (YG Collect):
```php
// Analyze form responses sentiment
foreach ($responses as $response) {
    $sentiment = $this->aiService->analyzeSentiment($response->feedback);
    
    ResponseSentiment::create([
        'response_id' => $response->id,
        'score' => $sentiment['score'],
        'label' => $sentiment['label'],
        'confidence' => $sentiment['confidence']
    ]);
}

// Calculate overall sentiment
$averageSentiment = ResponseSentiment::whereHas('response.form', function($q) use ($formId) {
    $q->where('id', $formId);
})->avg('score');
```

**Response Format**:
```php
[
    'score' => 0.85,           // -1 (negative) to 1 (positive)
    'label' => 'positive',     // negative/neutral/positive
    'confidence' => 0.92       // 0 to 1
]
```

---

### 8. Named Entity Recognition (NER)

**Purpose**: Extract entities (people, organizations, locations, dates, etc.)

**Method**:
```php
public function extractEntities(string $text): array
```

**Usage Examples**:

**A. Auto-tagging Emails** (YG Mail):
```php
// Extract entities for smart tagging
$entities = $this->aiService->extractEntities($email->body);

// Create tags from entities
$tags = collect($entities)
    ->whereIn('type', ['PERSON', 'ORG', 'LOCATION'])
    ->pluck('text')
    ->unique()
    ->take(5);

foreach ($tags as $tag) {
    EmailTag::firstOrCreate([
        'email_id' => $email->id,
        'name' => $tag
    ]);
}
```

**B. Contact Relationship Mapping** (YG Contacts):
```php
// Extract people mentioned in email communications
$entities = $this->aiService->extractEntities($email->body);

$mentionedPeople = collect($entities)
    ->where('type', 'PERSON')
    ->pluck('text');

// Link contacts who are mentioned together
foreach ($mentionedPeople as $personName) {
    $mentionedContact = Contact::where('name', 'LIKE', "%{$personName}%")->first();
    
    if ($mentionedContact && $mentionedContact->id !== $email->from_contact_id) {
        ContactRelationship::firstOrCreate([
            'contact_id' => $email->from_contact_id,
            'related_contact_id' => $mentionedContact->id,
            'relationship_type' => 'mentioned_together'
        ]);
    }
}
```

**Entity Types**:
- `PERSON`: People names
- `ORG`: Organizations/companies
- `LOCATION`: Cities, countries, addresses
- `DATE`: Dates and times
- `MONEY`: Monetary amounts
- `PRODUCT`: Product names

**Response Format**:
```php
[
    [
        'text' => 'John Smith',
        'type' => 'PERSON',
        'confidence' => 0.95
    ],
    [
        'text' => 'Microsoft',
        'type' => 'ORG',
        'confidence' => 0.92
    ],
    [
        'text' => 'New York',
        'type' => 'LOCATION',
        'confidence' => 0.88
    ]
]
```

---

### 9. Image Description (Multimodal)

**Purpose**: Describe image content for accessibility and search

**Method**:
```php
public function describeImage(string $imageUrl): string
```

**Usage Examples**:

**A. Image Alt Text Generation** (YG Drive):
```php
// Generate alt text for uploaded images
public function uploadImage(Request $request)
{
    $image = $request->file('image');
    $path = $image->store('images');
    $url = Storage::url($path);
    
    // Generate description in background
    DescribeImageJob::dispatch($path, $url);
    
    return response()->json([
        'success' => true,
        'path' => $path,
        'url' => $url
    ]);
}
```

**Background Job**:
```php
// app/Jobs/DescribeImageJob.php
class DescribeImageJob implements ShouldQueue
{
    protected $path;
    protected $url;
    
    public function handle(YGAIService $aiService)
    {
        $description = $aiService->describeImage($this->url);
        
        // Store description for accessibility and search
        File::where('path', $this->path)->update([
            'alt_text' => $description,
            'ai_described_at' => now()
        ]);
        
        // Index description for search
        IndexContentForSearch::dispatch(
            'drive',
            'image_description',
            $this->path,
            [
                'title' => basename($this->path),
                'content' => $description,
                'type' => 'image'
            ]
        );
    }
}
```

**B. Accessibility Enhancement**:
```blade
<!-- Auto-generated alt text for screen readers -->
<img src="{{ $file->url }}" 
     alt="{{ $file->alt_text ?? 'Image' }}"
     title="{{ $file->name }}">

@if($file->ai_described_at)
    <small class="text-muted">
        <i class="fas fa-robot"></i> AI-described
    </small>
@endif
```

---

### 10. Translation

**Purpose**: Translate text between languages

**Method**:
```php
public function translateText(string $text, string $targetLang = 'en'): string
```

**Usage Examples**:

**A. Email Translation** (YG Mail):
```php
// Translate incoming emails to user's preferred language
public function showTranslated(Email $email)
{
    $userLanguage = auth()->user()->preferred_language ?? 'en';
    
    if ($email->language !== $userLanguage) {
        $translatedBody = $this->aiService->translateText(
            $email->body,
            $userLanguage
        );
        
        return view('emails.show', [
            'email' => $email,
            'translated_body' => $translatedBody,
            'original_language' => $email->language
        ]);
    }
    
    return view('emails.show', compact('email'));
}
```

**B. Multi-language Support**:
```php
// Supported language codes
$languages = [
    'en' => 'English',
    'ne' => 'Nepali',
    'hi' => 'Hindi',
    'es' => 'Spanish',
    'fr' => 'French',
    'de' => 'German',
    'zh' => 'Chinese',
    'ja' => 'Japanese',
    'ar' => 'Arabic'
];

// Translate document
$translatedContent = $this->aiService->translateText(
    $document->content,
    $targetLanguage
);
```

---

## 🚀 Implementation Checklist by Module

### YG Mail Integration

- [ ] Add `category` column to emails table
- [ ] Integrate `categorizeEmail()` in email receive/send flow
- [ ] Add Gmail-style tabs (Primary/Social/Promotions/Updates)
- [ ] Integrate `generateSmartReply()` in email view
- [ ] Add smart reply chips UI
- [ ] Implement `analyzeSentiment()` for priority flagging
- [ ] Add sentiment badges to emails
- [ ] Integrate `extractEntities()` for auto-tagging
- [ ] Add translation button for foreign language emails

**Estimated Effort**: 1 week

---

### YG DocX Integration

- [ ] Create `/api/documents/suggestions` endpoint
- [ ] Integrate `suggestDocumentEdits()` with real-time polling
- [ ] Add suggestions panel in editor UI
- [ ] Implement "Apply Fix" functionality
- [ ] Integrate `summarizeText()` for document previews
- [ ] Add summary card in document list
- [ ] Add translation feature for documents

**Estimated Effort**: 1 week

---

### YG Calendar Integration

- [ ] Create `/api/calendar/parse-event` endpoint
- [ ] Add natural language input field in create event modal
- [ ] Integrate `parseCalendarEvent()` for quick event creation
- [ ] Show parsed event preview before creating
- [ ] Add attendee extraction and invitation sending

**Estimated Effort**: 3-4 days

---

### YG Contacts Integration

- [ ] Add enrichment columns to contacts table
- [ ] Create `EnrichContactJob` background job
- [ ] Trigger enrichment on contact creation/update
- [ ] Display enriched info (company, job title, industry)
- [ ] Add confidence indicator for AI-enriched data
- [ ] Implement `extractEntities()` for relationship mapping

**Estimated Effort**: 3-4 days

---

### YG Drive Integration

- [ ] Add `alt_text` and `ai_described_at` columns to files table
- [ ] Create `DescribeImageJob` for image uploads
- [ ] Integrate `describeImage()` for accessibility
- [ ] Display AI-generated alt text
- [ ] Index image descriptions for search

**Estimated Effort**: 2-3 days

---

## 📊 Performance Considerations

### Caching Strategy

All AI methods include built-in caching:

| Method | Cache TTL | Cache Key Pattern |
|--------|-----------|-------------------|
| Smart Reply | 1 hour | `ai_smart_reply_{md5}` |
| Email Categorization | 24 hours | `ai_email_cat_{md5}` |
| Contact Enrichment | 7 days | `ai_contact_enrich_{md5}` |
| Text Summarization | 1 hour | `ai_summary_{md5}` |
| Image Description | 30 days | `ai_image_desc_{md5}` |

### Async Processing

For non-critical AI features, use background jobs:

```php
// Instead of blocking UI
$suggestions = $aiService->generateSmartReply($body);

// Use queue job
GenerateSmartReplyJob::dispatch($emailId, $body);
```

### Rate Limiting

Implement rate limiting on AI API calls:

```php
// In controller
public function getSuggestions(Request $request)
{
    $key = 'ai_suggestions:' . auth()->id();
    
    if (RateLimiter::tooManyAttempts($key, 10)) {
        return response()->json([
            'error' => 'Too many requests. Try again later.'
        ], 429);
    }
    
    RateLimiter::hit($key, 60); // 10 requests per minute
    
    // ... proceed with AI call
}
```

### Timeout Handling

All HTTP calls have 5-second timeout with graceful degradation:

```php
try {
    $result = $aiService->someMethod($data);
} catch (\Exception $e) {
    Log::error('AI service timeout', ['error' => $e->getMessage()]);
    // Return fallback/default value
}
```

---

## 🧪 Testing

### Unit Tests

```php
// tests/Unit/YGAIServiceTest.php
namespace Tests\Unit;

use App\Services\YGAIService;
use Tests\TestCase;

class YGAIServiceTest extends TestCase
{
    protected $aiService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->aiService = new YGAIService();
    }
    
    public function testSmartReplyGeneration()
    {
        $replies = $this->aiService->generateSmartReply(
            'Hello, can we schedule a meeting?',
            'John Doe'
        );
        
        $this->assertIsArray($replies);
        $this->assertCount(3, $replies);
    }
    
    public function testEmailCategorization()
    {
        $category = $this->aiService->categorizeEmail(
            'Your order has shipped',
            'Tracking number: 12345',
            'noreply@amazon.com'
        );
        
        $this->assertEquals('updates', $category);
    }
    
    public function testSentimentAnalysis()
    {
        $sentiment = $this->aiService->analyzeSentiment(
            'I love this product! It is amazing!'
        );
        
        $this->assertGreaterThan(0, $sentiment['score']);
        $this->assertEquals('positive', $sentiment['label']);
    }
}
```

### Integration Tests

```php
// tests/Feature/MailAIFeatureTest.php
namespace Tests\Feature;

use App\Models\Email;
use Tests\TestCase;

class MailAIFeatureTest extends TestCase
{
    public function testEmailCategorizationOnReceive()
    {
        $email = Email::factory()->create([
            'subject' => 'Special Offer: 50% Off!',
            'body' => 'Limited time deal on all products...',
            'from_email' => 'marketing@store.com'
        ]);
        
        // Trigger categorization (via observer or job)
        $this->postJson("/api/emails/{$email->id}/categorize");
        
        $email->refresh();
        $this->assertEquals('promotions', $email->category);
    }
    
    public function testSmartReplyEndpoint()
    {
        $email = Email::factory()->create();
        
        $response = $this->postJson("/api/emails/{$email->id}/smart-reply");
        
        $response->assertSuccessful()
                 ->assertJsonStructure([
                     'success',
                     'suggestions' => [
                         '*' => 'string'
                     ]
                 ]);
    }
}
```

---

## 📈 Monitoring & Analytics

### Logging

All AI calls are logged with performance metrics:

```php
// Log AI service usage
Log::info('YG AI API Call', [
    'method' => 'generateSmartReply',
    'duration_ms' => $duration,
    'cache_hit' => $cached,
    'user_id' => auth()->id()
]);
```

### Dashboard Metrics

Track these metrics in admin dashboard:

- Total AI API calls per day
- Average response time
- Cache hit rate
- Error rate
- Most used features
- Cost per user (if paid API)

```php
// Example metric collection
class AIMetricsService
{
    public function recordCall(string $method, float $duration, bool $cached)
    {
        AIMetric::create([
            'method' => $method,
            'duration_ms' => $duration * 1000,
            'cached' => $cached,
            'user_id' => auth()->id(),
            'created_at' => now()
        ]);
    }
    
    public function getDailyStats()
    {
        return AIMetric::whereDate('created_at', today())
            ->selectRaw('method, COUNT(*) as calls, AVG(duration_ms) as avg_duration')
            ->groupBy('method')
            ->get();
    }
}
```

---

## 🔐 Security Best Practices

1. **Input Validation**: Always validate and sanitize inputs before sending to AI
2. **Output Escaping**: Escape AI-generated content in HTML to prevent XSS
3. **API Key Protection**: Never expose API keys in frontend code
4. **Rate Limiting**: Prevent abuse with per-user rate limits
5. **Content Filtering**: Filter sensitive data from logs
6. **HTTPS Only**: All AI API calls must use HTTPS

```php
// Example: Sanitize before AI call
$cleanText = strip_tags($userInput);
$cleanText = substr($cleanText, 0, 5000); // Limit length

$result = $aiService->someMethod($cleanText);

// Escape output in Blade
{{ $result }}  // Laravel auto-escapes
```

---

## 🎯 Go-Live Checklist

Before deploying AI features:

- [ ] YG AI service is running and accessible
- [ ] Environment variables configured correctly
- [ ] Database migrations executed
- [ ] Background jobs queue is running
- [ ] Rate limiting configured
- [ ] Error logging enabled
- [ ] Fallback mechanisms tested
- [ ] UI components styled and responsive
- [ ] Mobile compatibility verified
- [ ] Performance benchmarks met (<2s response time)
- [ ] User documentation prepared
- [ ] Admin monitoring dashboard ready

---

## 📞 Support & Troubleshooting

### Common Issues

**1. AI Service Unavailable**
- Check `YG_AI_URL` in `.env`
- Verify network connectivity
- Check YG AI service logs
- Fallback mechanisms should activate automatically

**2. Slow Response Times**
- Enable caching (already implemented)
- Use async jobs for non-critical features
- Increase timeout if needed (`YG_AI_TIMEOUT`)
- Consider CDN for AI service

**3. Incorrect Categorizations**
- Review fallback logic
- Adjust keyword lists
- Retrain AI model if using custom model
- Allow manual override by users

**4. Cache Not Working**
- Verify Redis/connection is running
- Check cache driver configuration
- Clear cache: `php artisan cache:clear`

### Debug Mode

Enable detailed logging:

```env
LOG_LEVEL=debug
YG_AI_DEBUG=true  # If supported by AI service
```

---

## 📚 Additional Resources

- **YG AI Documentation**: https://ai.ygxone.com/docs
- **API Reference**: https://ai.ygxone.com/api/docs
- **Support**: ai-support@ygxone.com
- **GitHub**: https://github.com/ygxone/yg-ai

---

**Status**: ✅ **READY FOR IMPLEMENTATION**  
**Last Updated**: May 4, 2026  
**Version**: 1.0.0
