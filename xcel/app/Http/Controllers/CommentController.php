<?php

namespace App\Http\Controllers;

use App\Models\CellComment;
use App\Models\Sheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * List all comments for a sheet.
     */
    public function index(Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canAccess(auth()->id()), 403);

        try {
            $comments = $sheet->comments()
                ->with(['user', 'resolver'])
                ->latest()
                ->get();

            return Inertia::render('Spreadsheets/Comments/Index', [
                'sheet' => $sheet,
                'comments' => $comments,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list comments for sheet', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load comments. Please try again.');
        }
    }

    /**
     * Store a new comment on a cell.
     */
    public function store(Request $request, Sheet $sheet)
    {
        abort_unless($sheet->spreadsheet->canAccess(auth()->id()), 403);

        try {
            $validated = $request->validate([
                'cell_address' => 'required|string',
                'content' => 'required|string|max:5000',
            ]);

            $comment = CellComment::create([
                'sheet_id' => $sheet->id,
                'cell_address' => strtoupper(trim($validated['cell_address'])),
                'user_id' => $request->user()->id,
                'content' => $validated['content'],
                'resolved' => false,
            ]);

            return redirect()->back()->with('success', 'Comment added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to add comment', [
                'error' => $e->getMessage(),
                'sheet_id' => $sheet->id,
            ]);

            return redirect()->back()->with('error', 'Failed to add comment. Please try again.');
        }
    }

    /**
     * Update an existing comment — only the comment author may edit it.
     */
    public function update(Request $request, CellComment $comment)
    {
        abort_unless($comment->user_id === auth()->id(), 403);

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
     * Mark a comment as resolved — author or spreadsheet editor may resolve.
     */
    public function resolve(CellComment $comment)
    {
        $spreadsheet = $comment->sheet->spreadsheet;
        abort_unless(
            $comment->user_id === auth()->id() || $spreadsheet->canEdit(auth()->id()),
            403
        );

        try {
            $comment->resolve(auth()->id());

            return redirect()->back()->with('success', 'Comment resolved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to resolve comment', [
                'error' => $e->getMessage(),
                'comment_id' => $comment->id,
            ]);

            return redirect()->back()->with('error', 'Failed to resolve comment. Please try again.');
        }
    }

    /**
     * Delete a comment — author or spreadsheet owner may delete.
     */
    public function delete(CellComment $comment)
    {
        $spreadsheet = $comment->sheet->spreadsheet;
        abort_unless(
            $comment->user_id === auth()->id() || $spreadsheet->user_id === auth()->id(),
            403
        );

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
