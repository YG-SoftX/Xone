<?php

namespace App\Modules\Forms\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormQuestion;
use App\Models\FormResponse;
use App\Services\EventService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormsController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display forms dashboard
     */
    public function index()
    {
        $forms = Form::where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_forms' => Form::where('user_id', auth()->id())->count(),
            'published_forms' => Form::where('user_id', auth()->id())->where('is_published', true)->count(),
            'total_responses' => FormResponse::whereIn('form_id', 
                Form::where('user_id', auth()->id())->pluck('id')
            )->count(),
        ];

        return view('forms.index', compact('forms', 'stats'));
    }

    /**
     * Create new form
     */
    public function create()
    {
        return view('forms.create');
    }

    /**
     * Store new form
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:form_templates,id',
        ]);

        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'uuid' => Str::uuid()->toString(),
            'theme' => json_encode([
                'primary_color' => '#4285f4',
                'background_color' => '#ffffff',
                'header_image' => null,
            ]),
            'confirmation_message' => json_encode([
                'message' => 'Thank you for your response!',
                'show_link' => true,
                'link_text' => 'Submit another response',
            ]),
        ]);

        // Load template if provided
        if ($validated['template_id']) {
            $template = \App\Models\FormTemplate::findOrFail($validated['template_id']);
            foreach ($template->questions as $questionData) {
                $form->questions()->create($questionData);
            }
        } else {
            // Add default first question
            $form->questions()->create([
                'title' => 'Untitled Question',
                'type' => 'short_text',
                'required' => false,
                'order' => 1,
            ]);
        }

        // Publish event
        $this->eventService->publish(
            'forms',
            'form_created',
            [
                'form_id' => $form->id,
                'title' => $form->title,
            ],
            auth()->id()
        );

        return redirect()->route('forms.edit', $form->id);
    }

    /**
     * Edit form (builder)
     */
    public function edit($formId)
    {
        $form = Form::findOrFail($formId);
        
        // Verify ownership or editor permission
        $isOwner = $form->user_id === auth()->id();
        $isEditor = $form->collaborators()
            ->where('user_id', auth()->id())
            ->whereIn('role', ['owner', 'editor'])
            ->exists();
        
        abort_unless($isOwner || $isEditor, 403);

        $questions = $form->questions()->orderBy('order')->get();

        return view('forms.edit', compact('form', 'questions'));
    }

    /**
     * Update form settings
     */
    public function update(Request $request, $formId)
    {
        $form = Form::findOrFail($formId);
        abort_unless($form->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'boolean',
            'accept_responses' => 'boolean',
            'require_login' => 'boolean',
            'allow_multiple_submissions' => 'boolean',
            'show_progress_bar' => 'boolean',
            'shuffle_questions' => 'boolean',
            'max_responses' => 'nullable|integer|min:1',
            'open_at' => 'nullable|date',
            'close_at' => 'nullable|date|after:open_at',
            'theme' => 'nullable|array',
            'confirmation_message' => 'nullable|array',
        ]);

        $form->update($validated);

        return response()->json(['success' => true]);
    }

    /**
     * Add question to form
     */
    public function addQuestion(Request $request, $formId)
    {
        $form = Form::findOrFail($formId);
        abort_unless($form->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'nullable|string',
            'type' => 'required|in:short_text,paragraph,multiple_choice,checkboxes,dropdown,linear_scale,date,time,file_upload,email,number,section',
            'required' => 'boolean',
            'options' => 'nullable|array',
            'validation' => 'nullable|array',
        ]);

        $maxOrder = $form->questions()->max('order') ?? 0;

        $question = $form->questions()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'required' => $validated['required'] ?? false,
            'options' => $validated['options'],
            'validation' => $validated['validation'],
            'order' => $maxOrder + 1,
        ]);

        return response()->json(['question' => $question]);
    }

    /**
     * Update question
     */
    public function updateQuestion(Request $request, $formId, $questionId)
    {
        $question = FormQuestion::findOrFail($questionId);
        abort_unless($question->form->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:short_text,paragraph,multiple_choice,checkboxes,dropdown,linear_scale,date,time,file_upload,email,number,section',
            'required' => 'boolean',
            'options' => 'nullable|array',
            'validation' => 'nullable|array',
            'order' => 'sometimes|integer',
        ]);

        $question->update($validated);

        return response()->json(['success' => true]);
    }

    /**
     * Delete question
     */
    public function deleteQuestion($formId, $questionId)
    {
        $question = FormQuestion::findOrFail($questionId);
        abort_unless($question->form->user_id === auth()->id(), 403);

        $question->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Show public form (for respondents)
     */
    public function show($uuid)
    {
        $form = Form::where('uuid', $uuid)->firstOrFail();
        
        abort_unless($form->is_published, 404);
        abort_unless($form->accept_responses, 403, 'This form is not accepting responses');

        // Check if form is within open/close dates
        if ($form->open_at && now()->lt($form->open_at)) {
            abort(403, 'This form has not opened yet');
        }
        if ($form->close_at && now()->gt($form->close_at)) {
            abort(403, 'This form has closed');
        }

        // Check max responses
        if ($form->max_responses && $form->response_count >= $form->max_responses) {
            abort(403, 'This form has reached maximum responses');
        }

        // Track view
        $this->trackView($form);

        $questions = $form->questions()->orderBy('order')->get();

        return view('forms.public.show', compact('form', 'questions'));
    }

    /**
     * Submit form response
     */
    public function submit(Request $request, $uuid)
    {
        $form = Form::where('uuid', $uuid)->firstOrFail();
        
        abort_unless($form->is_published && $form->accept_responses, 403);

        // Validate answers
        $answers = [];
        foreach ($form->questions as $question) {
            if ($question->required) {
                $request->validate([
                    "question_{$question->id}" => 'required',
                ]);
            }
            
            $answers[$question->id] = $request->input("question_{$question->id}");
        }

        // Create response
        $response = FormResponse::create([
            'form_id' => $form->id,
            'user_id' => auth()->check() ? auth()->id() : null,
            'respondent_email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'answers' => json_encode($answers),
            'submitted_at' => now(),
        ]);

        // Increment response count
        $form->increment('response_count');

        // Handle file uploads
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $questionId => $file) {
                $path = $file->store('form-uploads/' . $form->uuid);
                
                \App\Models\FormFileUpload::create([
                    'response_id' => $response->id,
                    'question_id' => $questionId,
                    'filename' => basename($path),
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'storage_path' => $path,
                ]);
            }
        }

        // Publish event
        $this->eventService->publish(
            'forms',
            'response_submitted',
            [
                'form_id' => $form->id,
                'response_id' => $response->id,
                'title' => $form->title,
            ],
            auth()->id()
        );

        return view('forms.public.thankyou', compact('form'));
    }

    /**
     * View form responses/analytics
     */
    public function responses($formId)
    {
        $form = Form::findOrFail($formId);
        abort_unless($form->user_id === auth()->id(), 403);

        $responses = $form->responses()->orderBy('submitted_at', 'desc')->paginate(50);
        $questions = $form->questions()->orderBy('order')->get();

        // Calculate basic stats
        $stats = [
            'total_responses' => $form->response_count,
            'avg_completion_time' => null, // TODO: Calculate from timestamps
            'completion_rate' => $form->response_count > 0 ? 100 : 0,
        ];

        return view('forms.responses', compact('form', 'responses', 'questions', 'stats'));
    }

    /**
     * Share form with collaborator
     */
    public function share(Request $request, $formId)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:editor,viewer',
        ]);

        $form = Form::findOrFail($formId);
        abort_unless($form->user_id === auth()->id(), 403);

        $user = \App\Models\User::where('email', $validated['email'])->first();
        
        if ($user) {
            $form->collaborators()->create([
                'user_id' => $user->id,
                'role' => $validated['role'],
            ]);
        }

        return redirect()->back()->with('success', 'Form shared successfully!');
    }

    /**
     * Delete form
     */
    public function destroy($formId)
    {
        $form = Form::findOrFail($formId);
        abort_unless($form->user_id === auth()->id(), 403);

        $form->delete();

        return redirect()->route('forms.index')->with('success', 'Form deleted');
    }

    /**
     * Track form view for analytics
     */
    protected function trackView(Form $form)
    {
        $today = now()->toDateString();
        
        $analytics = \App\Models\FormAnalytics::updateOrCreate(
            [
                'form_id' => $form->id,
                'date' => $today,
            ],
            [
                'views' => 0,
                'starts' => 0,
                'completions' => 0,
            ]
        );
        
        $analytics->increment('views');
    }
}
