<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Documentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class AdminDocController extends Controller
{
    /**
     * Available documentation categories.
     */
    private array $categories = ['api', 'sdk', 'guides', 'reference'];

    /**
     * List all docs with category filter.
     */
    public function index(Request $request)
    {
        try {
            $query = Documentation::with(['parent', 'children']);

            // Filter by category
            if ($request->filled('category')) {
                $query->byCategory($request->category);
            }

            // Filter by published status
            if ($request->filled('published')) {
                if ($request->published === '1') {
                    $query->published();
                } else {
                    $query->where('is_published', false);
                }
            }

            // Filter by parent (show only top-level or only children)
            if ($request->filled('parent')) {
                if ($request->parent === 'none') {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $request->parent);
                }
            }

            // Search by title
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%");
                });
            }

            $docs = $query->orderBy('order', 'asc')
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            // Get all parent docs for the dropdown
            $parentDocs = Documentation::whereNull('parent_id')
                ->orderBy('title')
                ->get();

            $stats = [
                'total' => Documentation::count(),
                'published' => Documentation::where('is_published', true)->count(),
                'drafts' => Documentation::where('is_published', false)->count(),
                'by_category' => Documentation::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
            ];

            return view('admin.docs.index', compact('docs', 'parentDocs', 'stats'));
        } catch (Exception $e) {
            Log::error('AdminDocController@index failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to load documentation.');
        }
    }

    /**
     * Show form to create a new doc.
     */
    public function create()
    {
        try {
            $categories = $this->categories;
            $parentDocs = Documentation::whereNull('parent_id')
                ->orderBy('title')
                ->get();

            return view('admin.docs.create', compact('categories', 'parentDocs'));
        } catch (Exception $e) {
            Log::error('AdminDocController@create failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.docs.index')
                ->with('error', 'Failed to load documentation creation form.');
        }
    }

    /**
     * Store a new documentation item.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'required|in:api,sdk,guides,reference',
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:documentation,slug',
                'summary' => 'nullable|string',
                'content' => 'required|string',
                'parent_id' => 'nullable|exists:documentation,id',
                'order' => 'nullable|integer|min:0',
                'is_published' => 'boolean',
            ]);

            // Auto-generate slug if not provided
            $slug = $validated['slug'] ?? Str::slug($validated['title']);

            // Ensure slug is unique
            $originalSlug = $slug;
            $counter = 1;
            while (Documentation::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $doc = Documentation::create([
                'category' => $validated['category'],
                'slug' => $slug,
                'title' => $validated['title'],
                'summary' => $validated['summary'] ?? null,
                'content' => $validated['content'],
                'parent_id' => $validated['parent_id'] ?? null,
                'order' => $validated['order'] ?? 0,
                'is_published' => !empty($validated['is_published']),
            ]);

            Log::info("Documentation created", [
                'doc_id' => $doc->id,
                'slug' => $doc->slug,
                'category' => $doc->category,
            ]);

            return redirect()->route('admin.docs.index')
                ->with('success', "Documentation '{$doc->title}' created successfully.");
        } catch (Exception $e) {
            Log::error('AdminDocController@store failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to create documentation.');
        }
    }

    /**
     * Show form to edit a documentation item.
     */
    public function edit($id)
    {
        try {
            $doc = Documentation::with(['parent', 'children'])->findOrFail($id);
            $categories = $this->categories;
            $parentDocs = Documentation::whereNull('parent_id')
                ->where('id', '!=', $id)
                ->orderBy('title')
                ->get();

            return view('admin.docs.edit', compact('doc', 'categories', 'parentDocs'));
        } catch (Exception $e) {
            Log::error('AdminDocController@edit failed: ' . $e->getMessage(), [
                'doc_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.docs.index')
                ->with('error', 'Failed to load documentation editor.');
        }
    }

    /**
     * Update a documentation item.
     */
    public function update(Request $request, $id)
    {
        try {
            $doc = Documentation::findOrFail($id);

            $validated = $request->validate([
                'category' => 'required|in:api,sdk,guides,reference',
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:documentation,slug,' . $id,
                'summary' => 'nullable|string',
                'content' => 'required|string',
                'parent_id' => 'nullable|exists:documentation,id',
                'order' => 'nullable|integer|min:0',
                'is_published' => 'boolean',
            ]);

            // Prevent self-referencing parent
            if (!empty($validated['parent_id']) && (int) $validated['parent_id'] === (int) $id) {
                return redirect()->back()->with('error', 'A document cannot be its own parent.');
            }

            // Auto-generate slug if not provided or changed
            $slug = $validated['slug'] ?? Str::slug($validated['title']);

            $doc->update([
                'category' => $validated['category'],
                'slug' => $slug,
                'title' => $validated['title'],
                'summary' => $validated['summary'] ?? null,
                'content' => $validated['content'],
                'parent_id' => $validated['parent_id'] ?? null,
                'order' => $validated['order'] ?? $doc->order,
                'is_published' => !empty($validated['is_published']),
            ]);

            Log::info("Documentation updated", [
                'doc_id' => $doc->id,
                'slug' => $doc->slug,
            ]);

            return redirect()->back()->with('success', "Documentation '{$doc->title}' updated successfully.");
        } catch (Exception $e) {
            Log::error('AdminDocController@update failed: ' . $e->getMessage(), [
                'doc_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to update documentation.');
        }
    }

    /**
     * Delete a documentation item.
     */
    public function destroy($id)
    {
        try {
            $doc = Documentation::findOrFail($id);

            // Check if this doc has children
            $childrenCount = $doc->children()->count();
            if ($childrenCount > 0) {
                return redirect()->back()->with('error', "Cannot delete '{$doc->title}' because it has {$childrenCount} child document(s). Reassign or delete them first.");
            }

            $title = $doc->title;
            $doc->delete();

            Log::info("Documentation deleted", [
                'doc_id' => $id,
                'title' => $title,
            ]);

            return redirect()->route('admin.docs.index')
                ->with('success', "Documentation '{$title}' deleted.");
        } catch (Exception $e) {
            Log::error('AdminDocController@destroy failed: ' . $e->getMessage(), [
                'doc_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to delete documentation.');
        }
    }

    /**
     * Reorder documentation (change order value).
     */
    public function reorder(Request $request)
    {
        try {
            $validated = $request->validate([
                'doc_id' => 'required|exists:documentation,id',
                'direction' => 'required|in:up,down',
            ]);

            $doc = Documentation::findOrFail($validated['doc_id']);
            $currentOrder = $doc->order;

            if ($validated['direction'] === 'up') {
                // Find the doc with the next lower order value and swap
                $sibling = Documentation::where('order', '<', $currentOrder)
                    ->where('category', $doc->category)
                    ->where('parent_id', $doc->parent_id)
                    ->orderBy('order', 'desc')
                    ->first();

                if ($sibling) {
                    $sibling->update(['order' => $currentOrder]);
                    $doc->update(['order' => $sibling->order]);
                }
            } else {
                // Find the doc with the next higher order value and swap
                $sibling = Documentation::where('order', '>', $currentOrder)
                    ->where('category', $doc->category)
                    ->where('parent_id', $doc->parent_id)
                    ->orderBy('order', 'asc')
                    ->first();

                if ($sibling) {
                    $sibling->update(['order' => $currentOrder]);
                    $doc->update(['order' => $sibling->order]);
                }
            }

            Log::info("Documentation reordered", [
                'doc_id' => $doc->id,
                'direction' => $validated['direction'],
                'new_order' => $doc->order,
            ]);

            return redirect()->back()->with('success', "Documentation '{$doc->title}' moved {$validated['direction']}.");
        } catch (Exception $e) {
            Log::error('AdminDocController@reorder failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to reorder documentation.');
        }
    }
}
