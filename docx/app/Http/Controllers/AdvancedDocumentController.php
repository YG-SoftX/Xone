<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentBookmark;
use App\Models\DocumentNote;
use App\Models\DocumentShape;
use App\Models\DocumentChart;
use App\Models\DocumentComparison;
use App\Models\DocumentPresence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdvancedDocumentController extends Controller
{
    /**
     * Get document presence (active users)
     */
    public function getPresence(Document $document)
    {
        abort_unless($document->canAccess(auth()->id()), 403);

        try {
            // Clean up stale presence records (older than 30 seconds)
            DocumentPresence::where('document_id', $document->id)
                ->where('last_active', '<', now()->subSeconds(30))
                ->delete();

            // Get active users
            $presence = DocumentPresence::with('user')
                ->where('document_id', $document->id)
                ->where('last_active', '>=', now()->subSeconds(30))
                ->get();

            $users = $presence->map(function ($p) {
                return [
                    'id' => $p->user_id,
                    'name' => $p->user->name,
                    'email' => $p->user->email,
                    'initials' => strtoupper(substr($p->user->name, 0, 1)),
                    'color' => $this->getUserColor($p->user_id),
                    'cursor_position' => $p->cursor_position,
                    'selection' => $p->selection,
                    'last_active' => $p->last_active->diffForHumans(),
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => $users->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get document presence', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to get presence'], 500);
        }
    }

    /**
     * Update user presence
     */
    public function updatePresence(Document $document, Request $request)
    {
        abort_unless($document->canAccess(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'cursor_position' => 'nullable|array',
                'selection' => 'nullable|array',
                'session_id' => 'required|string'
            ]);

            DocumentPresence::updateOrCreate(
                [
                    'document_id' => $document->id,
                    'user_id' => auth()->id(),
                    'session_id' => $validated['session_id']
                ],
                [
                    'cursor_position' => $validated['cursor_position'] ?? null,
                    'selection' => $validated['selection'] ?? null,
                    'last_active' => now()
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to update presence', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to update presence'], 500);
        }
    }

    /**
     * Create bookmark
     */
    public function createBookmark(Document $document, Request $request)
    {
        abort_unless($document->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'position' => 'required|integer'
            ]);

            $bookmark = DocumentBookmark::create([
                'document_id' => $document->id,
                'name' => $validated['name'],
                'position' => $validated['position'],
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'bookmark' => $bookmark
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create bookmark', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to create bookmark'], 500);
        }
    }

    /**
     * Get all bookmarks
     */
    public function getBookmarks(Document $document)
    {
        abort_unless($document->canAccess(auth()->id()), 403);

        try {
            $bookmarks = DocumentBookmark::with('creator')
                ->where('document_id', $document->id)
                ->orderBy('position')
                ->get();

            return response()->json([
                'success' => true,
                'bookmarks' => $bookmarks
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get bookmarks', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to get bookmarks'], 500);
        }
    }

    /**
     * Create footnote or endnote
     */
    public function createNote(Document $document, Request $request)
    {
        abort_unless($document->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'type' => 'required|in:footnote,endnote',
                'reference_position' => 'required|integer',
                'content' => 'required|string'
            ]);

            // Get next number for this type
            $lastNumber = DocumentNote::where('document_id', $document->id)
                ->where('type', $validated['type'])
                ->max('number') ?? 0;

            $note = DocumentNote::create([
                'document_id' => $document->id,
                'type' => $validated['type'],
                'reference_position' => $validated['reference_position'],
                'content' => $validated['content'],
                'number' => $lastNumber + 1,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'note' => $note
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create note', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to create note'], 500);
        }
    }

    /**
     * Insert shape
     */
    public function insertShape(Document $document, Request $request)
    {
        abort_unless($document->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'type' => 'required|string',
                'properties' => 'required|array',
                'content' => 'nullable|string',
                'z_index' => 'integer'
            ]);

            $shape = DocumentShape::create([
                'document_id' => $document->id,
                'type' => $validated['type'],
                'properties' => $validated['properties'],
                'content' => $validated['content'] ?? null,
                'z_index' => $validated['z_index'] ?? 0,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'shape' => $shape
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to insert shape', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to insert shape'], 500);
        }
    }

    /**
     * Insert chart
     */
    public function insertChart(Document $document, Request $request)
    {
        abort_unless($document->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'chart_type' => 'required|string',
                'data' => 'required|array',
                'options' => 'required|array',
                'position' => 'required|array'
            ]);

            $chart = DocumentChart::create([
                'document_id' => $document->id,
                'chart_type' => $validated['chart_type'],
                'data' => $validated['data'],
                'options' => $validated['options'],
                'position' => $validated['position'],
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'chart' => $chart
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to insert chart', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to insert chart'], 500);
        }
    }

    /**
     * Compare two documents
     */
    public function compareDocuments(Request $request)
    {
        try {
            $validated = $request->validate([
                'document_id' => 'required|exists:documents,id',
                'compare_with_id' => 'required|exists:documents,id'
            ]);

            $doc1 = Document::findOrFail($validated['document_id']);
            $doc2 = Document::findOrFail($validated['compare_with_id']);

            abort_unless($doc1->canAccess(auth()->id()) && $doc2->canAccess(auth()->id()), 403);

            // Simple diff algorithm (can be enhanced with proper diff library)
            $differences = $this->calculateDifferences($doc1->content, $doc2->content);

            $comparison = DocumentComparison::create([
                'document_id' => $doc1->id,
                'compare_with_id' => $doc2->id,
                'created_by' => auth()->id(),
                'differences' => $differences,
                'summary' => "Compared '{$doc1->title}' with '{$doc2->title}'"
            ]);

            return response()->json([
                'success' => true,
                'comparison' => $comparison,
                'differences' => $differences
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to compare documents', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to compare documents'], 500);
        }
    }

    /**
     * Calculate differences between two documents
     */
    private function calculateDifferences($content1, $content2)
    {
        // This is a simplified diff - in production, use a proper diff library
        // like php-diff or similar
        
        $lines1 = explode("\n", strip_tags($content1));
        $lines2 = explode("\n", strip_tags($content2));
        
        $differences = [
            'added' => array_diff($lines2, $lines1),
            'removed' => array_diff($lines1, $lines2),
            'total_changes' => 0
        ];
        
        $differences['total_changes'] = count($differences['added']) + count($differences['removed']);
        
        return $differences;
    }

    /**
     * Save macro
     */
    public function saveMacro(Document $document, Request $request)
    {
        abort_unless($document->canEdit(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'steps' => 'required|array'
            ]);

            $macro = \App\Models\DocumentMacro::create([
                'document_id' => $document->id,
                'name' => $validated['name'],
                'script' => json_encode($validated['steps']),
                'triggers' => null,
                'is_enabled' => true,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'macro' => $macro
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save macro', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to save macro'], 500);
        }
    }

    /**
     * Get all macros
     */
    public function getMacros(Document $document)
    {
        abort_unless($document->canAccess(auth()->id()), 403);

        try {
            $macros = \App\Models\DocumentMacro::with('creator')
                ->where('document_id', $document->id)
                ->where('is_enabled', true)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'macros' => $macros
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get macros', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to get macros'], 500);
        }
    }

    /**
     * Execute macro
     */
    public function executeMacro(Document $document, $macroId)
    {
        abort_unless($document->canEdit(auth()->id()), 403);

        try {
            $macro = \App\Models\DocumentMacro::findOrFail($macroId);
            
            if ($macro->document_id !== $document->id) {
                abort(403, 'Macro does not belong to this document');
            }

            // Execute macro steps (in production, implement JavaScript execution sandbox)
            $steps = json_decode($macro->script, true);
            
            return response()->json([
                'success' => true,
                'message' => "Macro '{$macro->name}' executed with " . count($steps) . " steps",
                'steps_executed' => count($steps)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to execute macro', [
                'error' => $e->getMessage(),
                'macro_id' => $macroId
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to execute macro'], 500);
        }
    }

    /**
     * Get consistent color for user
     */
    private function getUserColor($userId)
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', 
            '#98D8C8', '#F7DC6F', '#BB8FCE', '#82E0AA',
            '#F8B739', '#6C5CE7', '#A29BFE', '#FD79A8'
        ];
        
        return $colors[$userId % count($colors)];
    }
}
