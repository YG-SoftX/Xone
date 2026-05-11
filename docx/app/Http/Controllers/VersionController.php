<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VersionController extends Controller
{
    /**
     * Display a listing of all versions for a document.
     */
    public function index(Document $document)
    {
        try {
            $versions = $document->versions()
                ->with(['user'])
                ->latest('version_number')
                ->get();

            return Inertia::render('Documents/Versions/Index', [
                'document' => $document,
                'versions' => $versions,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list document versions', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load versions. Please try again.');
        }
    }

    /**
     * Display the specified version.
     */
    public function show(DocumentVersion $version)
    {
        try {
            $version->load(['user', 'document']);

            return Inertia::render('Documents/Versions/Show', [
                'version' => $version,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to view document version', [
                'error' => $e->getMessage(),
                'version_id' => $version->id,
            ]);

            return redirect()->back()->with('error', 'Failed to load version. Please try again.');
        }
    }

    /**
     * Restore the document to a specific version.
     */
    public function restore(DocumentVersion $version)
    {
        try {
            $document = $version->document;

            // Create a version before restoring (backup current state)
            $document->createVersion(auth()->user(), 'Restored to version ' . $version->version_number);

            // Restore the content from the selected version
            $document->content = $version->content;
            $document->content_json = $version->content_json;
            $document->save();

            // Record activity
            DocumentActivity::recordActivity(
                $document->id,
                auth()->id(),
                'version_restored',
                ['version_id' => $version->id, 'version_number' => $version->version_number]
            );

            return redirect()->route('documents.show', $document)
                ->with('success', 'Document restored to version ' . $version->version_number . '.');
        } catch (\Exception $e) {
            Log::error('Failed to restore document version', [
                'error' => $e->getMessage(),
                'version_id' => $version->id,
            ]);

            return redirect()->back()->with('error', 'Failed to restore version. Please try again.');
        }
    }

    /**
     * Remove the specified version.
     */
    public function delete(DocumentVersion $version)
    {
        try {
            $version->delete();

            return redirect()->back()->with('success', 'Version deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete document version', [
                'error' => $e->getMessage(),
                'version_id' => $version->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete version. Please try again.');
        }
    }
}
