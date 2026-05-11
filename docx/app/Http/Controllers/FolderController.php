<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FolderController extends Controller
{
    /**
     * Display a listing of folders with tree structure.
     */
    public function index(Request $request)
    {
        try {
            $folders = Folder::where('user_id', $request->user()->id)
                ->with(['parent', 'children'])
                ->orderBy('name')
                ->get();

            // Build tree structure
            $tree = $this->buildTree($folders);

            return Inertia::render('Folders/Index', [
                'folders' => $folders,
                'tree' => $tree,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list folders', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to load folders. Please try again.');
        }
    }

    /**
     * Store a newly created folder.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|exists:folders,id',
                'color' => 'nullable|string|max:7',
                'icon' => 'nullable|string|max:50',
            ]);

            $validated['user_id'] = $request->user()->id;

            Folder::create($validated);

            return redirect()->back()->with('success', 'Folder created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create folder', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return redirect()->back()->with('error', 'Failed to create folder. Please try again.')->withInput();
        }
    }

    /**
     * Update the specified folder.
     */
    public function update(Request $request, Folder $folder)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'parent_id' => 'nullable|exists:folders,id',
                'color' => 'nullable|string|max:7',
                'icon' => 'nullable|string|max:50',
            ]);

            // Prevent circular reference
            if (isset($validated['parent_id']) && $validated['parent_id'] == $folder->id) {
                return redirect()->back()->with('error', 'A folder cannot be its own parent.');
            }

            $folder->update($validated);

            return redirect()->back()->with('success', 'Folder updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update folder', [
                'error' => $e->getMessage(),
                'folder_id' => $folder->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update folder. Please try again.');
        }
    }

    /**
     * Remove the specified folder.
     */
    public function delete(Folder $folder)
    {
        try {
            // Check if folder has documents
            if ($folder->documents()->count() > 0) {
                return redirect()->back()->with('error', 'Cannot delete folder that contains documents. Move or delete the documents first.');
            }

            // Check if folder has child folders
            if ($folder->children()->count() > 0) {
                return redirect()->back()->with('error', 'Cannot delete folder that contains subfolders. Move or delete the subfolders first.');
            }

            $folder->delete();

            return redirect()->back()->with('success', 'Folder deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete folder', [
                'error' => $e->getMessage(),
                'folder_id' => $folder->id,
            ]);

            return redirect()->back()->with('error', 'Failed to delete folder. Please try again.');
        }
    }

    /**
     * Build a tree structure from a flat list of folders.
     *
     * @param \Illuminate\Support\Collection $folders
     * @param int|null $parentId
     * @return array
     */
    private function buildTree($folders, $parentId = null)
    {
        $tree = [];

        foreach ($folders as $folder) {
            if ($folder->parent_id === $parentId) {
                $children = $this->buildTree($folders, $folder->id);
                $folderArray = $folder->toArray();
                $folderArray['children'] = $children;
                $tree[] = $folderArray;
            }
        }

        return $tree;
    }
}
