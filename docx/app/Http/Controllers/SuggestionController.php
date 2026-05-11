<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentSuggestion;
use App\Models\DocumentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SuggestionController extends Controller
{
    /**
     * Display a listing of suggestions for a document.
     */
    public function index(Document $document)
    {
        try {
            $suggestions = $document->suggestions()
                ->with(['user'])
                ->where('status', 'pending')
                ->latest()
                ->get();

            return Inertia::render('Documents/Suggestions/Index', [
                'document' => $document,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list suggestions', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load suggestions. Please try again.');
        }
    }

    /**
     * Store a newly created suggestion.
     */
    public function store(Request $request, Document $document)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string',
                'original_content' => 'required|string',
                'position_start' => 'required|integer|min:0',
                'position_end' => 'required|integer|gte:position_start',
            ]);

            $validated['document_id'] = $document->id;
            $validated['user_id'] = $request->user()->id;
            $validated['status'] = 'pending';

            DocumentSuggestion::create($validated);

            return redirect()->back()->with('success', 'Suggestion added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to add suggestion', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to add suggestion. Please try again.');
        }
    }

    /**
     * Accept and apply a suggestion.
     */
    public function accept(DocumentSuggestion $suggestion)
    {
        try {
            $suggestion->accept(auth()->id());

            // Record activity
            DocumentActivity::recordActivity(
                $suggestion->document_id,
                auth()->id(),
                'suggestion_accepted',
                ['suggestion_id' => $suggestion->id]
            );

            return redirect()->back()->with('success', 'Suggestion accepted and applied.');
        } catch (\Exception $e) {
            Log::error('Failed to accept suggestion', [
                'error' => $e->getMessage(),
                'suggestion_id' => $suggestion->id,
            ]);

            return redirect()->back()->with('error', 'Failed to accept suggestion. Please try again.');
        }
    }

    /**
     * Reject a suggestion.
     */
    public function reject(DocumentSuggestion $suggestion)
    {
        try {
            $suggestion->reject(auth()->id());

            // Record activity
            DocumentActivity::recordActivity(
                $suggestion->document_id,
                auth()->id(),
                'suggestion_rejected',
                ['suggestion_id' => $suggestion->id]
            );

            return redirect()->back()->with('success', 'Suggestion rejected.');
        } catch (\Exception $e) {
            Log::error('Failed to reject suggestion', [
                'error' => $e->getMessage(),
                'suggestion_id' => $suggestion->id,
            ]);

            return redirect()->back()->with('error', 'Failed to reject suggestion. Please try again.');
        }
    }
}
