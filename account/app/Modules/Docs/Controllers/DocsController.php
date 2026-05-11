<?php

namespace App\Modules\Docs\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DocDocument;
use App\Services\EventService;
use Illuminate\Http\Request;

class DocsController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Display documents home
     */
    public function index(Request $request)
    {
        $type = $request->input('type', 'all');
        
        $query = DocDocument::where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc');

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $documents = $query->paginate(50);

        return view('docs.index', compact('documents', 'type'));
    }

    /**
     * Create new document
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:document,spreadsheet,presentation',
            'template_id' => 'nullable|exists:docs_templates,id',
        ]);

        // Load template if provided
        $content = '';
        $metadata = null;
        
        if ($validated['template_id']) {
            $template = \App\Models\DocTemplate::findOrFail($validated['template_id']);
            $content = $template->content;
            $metadata = $template->metadata;
        }

        $document = DocDocument::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $content,
            'metadata' => $metadata,
            'last_edited_at' => now(),
        ]);

        // Publish event
        $this->eventService->publish(
            'docs',
            'document_created',
            [
                'document_id' => $document->id,
                'title' => $document->title,
                'type' => $document->type,
            ],
            auth()->id()
        );

        return redirect()->route('docs.edit', $document->id);
    }

    /**
     * Edit document
     */
    public function edit($documentId)
    {
        $document = DocDocument::findOrFail($documentId);
        
        // Verify ownership or collaboration permission
        $isOwner = $document->user_id === auth()->id();
        $isCollaborator = $document->collaborators()
            ->where('user_id', auth()->id())
            ->whereIn('permission', ['edit', 'owner'])
            ->exists();
        
        abort_unless($isOwner || $isCollaborator, 403);

        // Track editing session
        \App\Models\DocEditingSession::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        return view('docs.edit', compact('document'));
    }

    /**
     * Update document
     */
    public function update(Request $request, $documentId)
    {
        $document = DocDocument::findOrFail($documentId);
        
        // Verify ownership or collaboration permission
        $isOwner = $document->user_id === auth()->id();
        $isCollaborator = $document->collaborators()
            ->where('user_id', auth()->id())
            ->whereIn('permission', ['edit', 'owner'])
            ->exists();
        
        abort_unless($isOwner || $isCollaborator, 403);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'metadata' => 'sometimes|json',
        ]);

        // Save revision before updating
        $document->revisions()->create([
            'user_id' => auth()->id(),
            'revision_number' => 'v' . ($document->revisions()->count() + 1),
            'content_snapshot' => $document->content,
            'change_summary' => 'Auto-saved revision',
        ]);

        $document->update(array_merge($validated, [
            'last_edited_at' => now(),
        ]));

        // Update editing session
        \App\Models\DocEditingSession::where('document_id', $document->id)
            ->where('user_id', auth()->id())
            ->whereNull('ended_at')
            ->update(['last_activity_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Add comment
     */
    public function addComment(Request $request, $documentId)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'parent_comment_id' => 'nullable|exists:docs_comments,id',
            'position' => 'nullable|json',
        ]);

        $document = DocDocument::findOrFail($documentId);

        $comment = $document->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            'parent_comment_id' => $validated['parent_comment_id'],
            'position' => $validated['position'],
        ]);

        return response()->json(['comment' => $comment]);
    }

    /**
     * Share document with collaborator
     */
    public function share(Request $request, $documentId)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'permission' => 'required|in:view,comment,edit',
        ]);

        $document = DocDocument::findOrFail($documentId);
        abort_unless($document->user_id === auth()->id(), 403);

        // Find user by email
        $user = \App\Models\User::where('email', $validated['email'])->first();
        
        if ($user) {
            $document->collaborators()->create([
                'user_id' => $user->id,
                'permission' => $validated['permission'],
            ]);
        }

        // TODO: Send email invitation

        return redirect()->back()->with('success', 'Document shared successfully!');
    }

    /**
     * Delete document
     */
    public function destroy($documentId)
    {
        $document = DocDocument::findOrFail($documentId);
        abort_unless($document->user_id === auth()->id(), 403);

        $document->delete();

        return redirect()->route('docs.index')->with('success', 'Document deleted');
    }
}
