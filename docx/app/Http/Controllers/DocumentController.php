<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Template;
use App\Models\DocumentVersion;
use App\Models\DocumentActivity;
use App\Services\YgAccountEventPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    protected $eventPublisher;

    public function __construct(YgAccountEventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }
    /**
     * Display a listing of documents with filters.
     */
    public function index(Request $request)
    {
        try {
            $query = Document::with(['folder', 'user'])
                ->where('user_id', $request->user()->id)
                ->latest();

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('title', 'like', "%{$search}%");
            }

            if ($request->filled('type')) {
                $query->where('document_type', $request->input('type'));
            }

            if ($request->filled('folder')) {
                $query->where('folder_id', $request->input('folder'));
            }

            $documents = $query->get();
            $folders = Folder::where('user_id', $request->user()->id)->get();
            $templates = Template::where(function ($q) {
                $q->where('is_public', true)->orWhere('is_system', true);
            })->get();

            return view('documents.index', [
                'documents' => $documents,
                'folders' => $folders,
                'templates' => $templates,
                'filters' => $request->only(['search', 'type', 'folder']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list documents', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to load documents.');
        }
    }

    /**
     * Display the specified document.
     */
    public function show(Document $document)
    {
        abort_unless($document->canAccess(auth()->id()), 403);

        try {
            $document->load(['shares.user', 'shares.createdBy', 'comments.user', 'suggestions.user']);
            
            // Determine permissions
            $userId = auth()->id();
            $canEdit = $document->user_id === $userId || 
                      $document->shares->where('user_id', $userId)->where('permission', 'edit')->isNotEmpty();
            $canComment = $canEdit || 
                         $document->shares->where('user_id', $userId)->where('permission', 'comment')->isNotEmpty();

            return view('documents.editor', [
                'document' => $document,
                'canEdit' => $canEdit,
                'canComment' => $canComment,
                'comments' => $document->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'user_name' => $comment->user ? $comment->user->name : 'Anonymous',
                        'content' => $comment->content,
                        'created_at' => $comment->created_at->toDateTimeString(),
                        'resolved_at' => $comment->resolved_at?->toDateTimeString(),
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to view document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load document.');
        }
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(Request $request)
    {
        try {
            $folders = Folder::where('user_id', $request->user()->id)->get();
            $templates = Template::where(function ($q) {
                $q->where('is_public', true)->orWhere('is_system', true);
            })->get();

            return view('documents.create', [
                'folders' => $folders,
                'templates' => $templates,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load document creation form', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to load creation form.');
        }
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'nullable|string',
                'content_json' => 'nullable|array',
                'document_type' => 'nullable|string',
                'folder_id' => 'nullable|exists:folders,id',
                'template_id' => 'nullable|exists:templates,id',
            ]);

            if ($request->filled('template_id')) {
                $template = Template::findOrFail($request->input('template_id'));
                $validated['content'] = $template->content;
                $validated['content_json'] = $template->content_json;
            }

            $validated['user_id'] = $request->user()->id;
            $validated['status'] = 'draft';

            $document = Document::create($validated);
            $document->createVersion($request->user(), 'Initial creation');

            return redirect()->route('documents.show', $document)->with('success', 'Document created.');
        } catch (\Exception $e) {
            Log::error('Failed to create document', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to create document.');
        }
    }

    /**
     * Update the specified document.
     */
    public function update(Request $request, Document $document)
    {
        abort_unless($document->user_id === $request->user()->id, 403);

        try {
            $validated = $request->validate([
                'content' => 'nullable|string',
                'content_json' => 'nullable|array',
                'title' => 'nullable|string|max:255',
            ]);

            $document->update($validated);
            return redirect()->back()->with('success', 'Document updated.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Update failed.');
        }
    }

    /**
     * Archive the specified document.
     */
    public function archive(Document $document)
    {
        abort_unless($document->user_id === auth()->id(), 403);
        $document->update(['status' => 'archived']);
        return redirect()->back()->with('success', 'Document archived.');
    }

    /**
     * Delete the specified document.
     */
    public function delete(Document $document)
    {
        abort_unless($document->user_id === auth()->id(), 403);
        $document->delete();
        return redirect()->route('home')->with('success', 'Document deleted.');
    }

    /**
     * Publish document to Imperial Journal.
     */
    public function publishToJournal(Document $document)
    {
        abort_unless($document->user_id === Auth::id(), 403);

        try {
            $document->update(['status' => 'published']);

            // Fire cross-node event to Imperial Journal (using the publisher)
            $this->eventPublisher->publishDocumentPublished(
                $document->id,
                Auth::id(),
                $document->title,
                route('documents.show', $document)
            );

            return redirect()->back()->with('success', 'Document successfully published to Imperial Journal.');
        } catch (\Exception $e) {
            Log::error('Failed to publish document', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to publish to Imperial Journal.');
        }
    }
}
