<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use App\Models\DocumentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ShareController extends Controller
{
    /**
     * Display a listing of document shares.
     */
    public function index(Document $document)
    {
        try {
            $shares = $document->shares()->with(['user', 'createdBy'])->latest()->get();

            return Inertia::render('Documents/Shares/Index', [
                'document' => $document,
                'shares' => $shares,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list document shares', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load shares. Please try again.');
        }
    }

    /**
     * Share a document with a user or email.
     */
    public function store(Request $request, Document $document)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'permission' => 'required|in:view,comment,edit',
            ]);

            // Find user by email
            $user = User::where('email', $validated['email'])->first();

            // Check if already shared
            $existingShare = DocumentShare::where('document_id', $document->id)
                ->where(function ($q) use ($user, $validated) {
                    if ($user) {
                        $q->where('user_id', $user->id);
                    }
                    $q->orWhere('email', $validated['email']);
                })
                ->first();

            if ($existingShare) {
                return redirect()->back()->with('error', 'This user already has access to this document.');
            }

            // Create share
            $share = DocumentShare::create([
                'document_id' => $document->id,
                'user_id' => $user?->id,
                'email' => $validated['email'],
                'permission' => $validated['permission'],
                'created_by' => $request->user()->id,
            ]);

            // Record activity
            DocumentActivity::recordActivity(
                $document->id,
                $request->user()->id,
                'shared',
                ['email' => $validated['email'], 'permission' => $validated['permission']]
            );

            return redirect()->back()->with('success', 'Document shared successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to share document', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to share document. Please try again.');
        }
    }

    /**
     * Update the permission of a document share.
     */
    public function update(Request $request, DocumentShare $share)
    {
        try {
            $validated = $request->validate([
                'permission' => 'required|in:view,comment,edit',
            ]);

            $share->update($validated);

            // Record activity
            DocumentActivity::recordActivity(
                $share->document_id,
                $request->user()->id,
                'permission_updated',
                ['share_id' => $share->id, 'new_permission' => $validated['permission']]
            );

            return redirect()->back()->with('success', 'Permission updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update share permission', [
                'error' => $e->getMessage(),
                'share_id' => $share->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update permission. Please try again.');
        }
    }

    /**
     * Revoke access to a document share.
     */
    public function revoke(DocumentShare $share)
    {
        try {
            $documentId = $share->document_id;
            $shareEmail = $share->email;

            $share->delete();

            // Record activity
            DocumentActivity::recordActivity(
                $documentId,
                $share->created_by,
                'share_revoked',
                ['share_id' => $share->id, 'email' => $shareEmail]
            );

            return redirect()->back()->with('success', 'Access revoked successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to revoke share', [
                'error' => $e->getMessage(),
                'share_id' => $share->id,
            ]);

            return redirect()->back()->with('error', 'Failed to revoke access. Please try again.');
        }
    }
}
