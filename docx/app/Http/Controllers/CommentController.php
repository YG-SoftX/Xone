<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * Display a listing of comments for a document.
     */
    public function index(Document $document)
    {
        try {
            $comments = $document->comments()
                ->with(['user', 'replies.user'])
                ->whereNull('parent_id')
                ->latest()
                ->get();

            return Inertia::render('Documents/Comments/Index', [
                'document' => $document,
                'comments' => $comments,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list comments', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load comments. Please try again.');
        }
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request, Document $document)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:5000',
                'parent_id' => 'nullable|exists:document_comments,id',
            ]);

            $validated['document_id'] = $document->id;
            $validated['user_id'] = $request->user()->id;
            $validated['resolved'] = false;

            $comment = DocumentComment::create($validated);

            return redirect()->back()->with('success', 'Comment added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to add comment', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to add comment. Please try again.');
        }
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, DocumentComment $comment)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:5000',
            ]);

            $comment->update($validated);

            return redirect()->back()->with('success', 'Comment updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update comment', [
                'error' => $e->getMessage(),
                'comment_id' => $comment->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update comment. Please try again.');
        }
    }

    /**
     * Mark a comment as resolved.
     */
    public function resolve(DocumentComment $comment)
    {
        try {
            $comment->resolve(auth()->id());

            return redirect()->back()->with('success', 'Comment marked as resolved.');
        } catch (\Exception $e) {
            Log::error('Failed to resolve comment', [
                'error' => $e->getMessage(),
                'comment_id' => $comment->id,
            ]);

            return redirect()->back()->with('error', 'Failed to resolve comment. Please try again.');
        }
    }

    /**
     * Remove the specified comment.
     */
    public function delete(DocumentComment $comment)
    {
        try {
            $comment->delete();

            return redirect()->back()->with('success', 'Comment deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete comment', [
                'error' => $e->getMessage(),
                'comment_id' => $comment->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete comment. Please try again.');
        }
    }
}
