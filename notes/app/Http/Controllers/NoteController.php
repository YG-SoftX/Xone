<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteChecklist;
use App\Services\NoteAiService;
use App\Services\YgAccountEventPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    protected $eventPublisher;

    public function __construct(YgAccountEventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }

    /**
     * Display all notes (pinned + others)
     */
    public function index(Request $request)
    {
        $view = $request->get('view', 'grid'); // grid or list
        $search = $request->get('search');
        
        $notes = Note::where('user_id', Auth::id())
            ->active()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        $pinnedNotes = $notes->where('is_pinned', true);
        $otherNotes = $notes->where('is_pinned', false);

        return view('notes.index', compact('pinnedNotes', 'otherNotes', 'view', 'search'));
    }

    /**
     * Store a new note
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|in:white,yellow,green,blue,red,purple,orange',
            'labels' => 'nullable|array',
        ]);

        $note = Note::create([
            ...$validated,
            'user_id' => Auth::id(),
            'color' => $validated['color'] ?? 'white',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'note' => $note]);
        }

        return redirect()->route('notes.index')->with('success', 'Note created');
    }

    /**
     * Update an existing note
     */
    public function update(Request $request, Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'color' => 'nullable|string|in:white,yellow,green,blue,red,purple,orange',
            'labels' => 'nullable|array',
        ]);

        $note->update($validated);

        return response()->json(['success' => true, 'note' => $note]);
    }

    /**
     * Delete note (move to trash)
     */
    public function destroy(Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);
        
        $note->moveToTrash();
        
        return response()->json(['success' => true]);
    }

    /**
     * Pin/unpin a note
     */
    public function pin(Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);
        $note->togglePin();
        return response()->json(['success' => true, 'is_pinned' => $note->is_pinned]);
    }

    /**
     * Archive a note
     */
    public function archive(Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);
        $note->archive();
        return response()->json(['success' => true]);
    }

    /**
     * Display archived notes
     */
    public function archived()
    {
        $notes = Note::where('user_id', Auth::id())
            ->where('is_archived', true)
            ->orderByDesc('archived_at')
            ->get();

        return view('notes.archived', compact('notes'));
    }

    /**
     * Display trashed notes
     */
    public function trash()
    {
        $notes = Note::where('user_id', Auth::id())
            ->where('is_deleted', true)
            ->orderByDesc('deleted_at')
            ->get();

        return view('notes.trash', compact('notes'));
    }

    /**
     * Restore note from trash
     */
    public function restore($id)
    {
        $note = Note::findOrFail($id);
        abort_unless($note->user_id === Auth::id(), 403);
        
        $note->restoreFromTrash();
        
        return redirect()->back()->with('success', 'Note restored');
    }

    /**
     * Permanently delete note
     */
    public function deletePermanently($id)
    {
        $note = Note::findOrFail($id);
        abort_unless($note->user_id === Auth::id(), 403);
        
        $note->delete();
        
        return redirect()->back()->with('success', 'Note permanently deleted');
    }

    /**
     * Add checklist item to note
     */
    public function addChecklistItem(Request $request, Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'item_text' => 'required|string|max:500',
        ]);

        $order = $note->checklists()->max('order') + 1;

        NoteChecklist::create([
            'note_id' => $note->id,
            'item_text' => $validated['item_text'],
            'order' => $order,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Toggle checklist item completion
     */
    public function toggleChecklistItem(NoteChecklist $item)
    {
        abort_unless($item->note->user_id === Auth::id(), 403);
        $item->toggle();
        return response()->json(['success' => true, 'is_completed' => $item->is_completed]);
    }

    /**
     * Delete checklist item
     */
    public function deleteChecklistItem(NoteChecklist $item)
    {
        abort_unless($item->note->user_id === Auth::id(), 403);
        $item->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Poll for real-time updates
     */
    public function poll(Request $request)
    {
        $since = $request->query('since', now()->subMinutes(5));

        $notes = Note::where('user_id', Auth::id())
            ->active()
            ->where('updated_at', '>', $since)
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'notes' => $notes,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * AI Summarize Note
     */
    public function summarize(Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);
        $summary = NoteAiService::summarize($note->content ?? '');
        return response()->json(['summary' => $summary]);
    }

    /**
     * Publish Note to Imperial Journal
     */
    public function publishToJournal(Note $note)
    {
        abort_unless($note->user_id === Auth::id(), 403);

        $journalData = NoteAiService::prepareForJournal($note->title ?? 'Untitled Note', $note->content ?? '');

        // Fire cross-node event to Imperial Journal
        $this->eventPublisher->publishNotePublished(
            $note->id,
            Auth::id(),
            $journalData['title'],
            route('notes.index') // Deep link to the note, or front-end URL
        );

        return response()->json(['success' => true, 'message' => 'Note published to Imperial Journal.']);
    }
}
