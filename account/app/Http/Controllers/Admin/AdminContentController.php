<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminContentController extends Controller
{
    /**
     * Default content keys.
     */
    private array $contentKeys = [
        'terms_of_service' => 'Terms of Service',
        'privacy_policy' => 'Privacy Policy',
        'about_us' => 'About Us',
        'help_center' => 'Help Center',
        'faq' => 'FAQ',
        'contact_us' => 'Contact Us',
    ];

    /**
     * List all frontend content items.
     */
    public function index(Request $request)
    {
        try {
            $query = FrontendContent::query();

            // Filter by locale
            if ($request->filled('locale')) {
                $query->byLocale($request->locale);
            }

            // Filter by published status
            if ($request->filled('published')) {
                if ($request->published === '1') {
                    $query->published();
                } else {
                    $query->where('is_published', false);
                }
            }

            // Search by key or title
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            }

            $contents = $query->orderBy('updated_at', 'desc')->paginate(50);

            $stats = [
                'total' => FrontendContent::count(),
                'published' => FrontendContent::where('is_published', true)->count(),
                'drafts' => FrontendContent::where('is_published', false)->count(),
            ];

            return view('admin.content.index', compact('contents', 'stats'));
        } catch (Exception $e) {
            Log::error('AdminContentController@index failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to load frontend content.');
        }
    }

    /**
     * Show editor for a specific content key.
     */
    public function edit($id)
    {
        try {
            $content = FrontendContent::findOrFail($id);

            return view('admin.content.edit', compact('content'));
        } catch (Exception $e) {
            Log::error('AdminContentController@edit failed: ' . $e->getMessage(), [
                'content_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.content.index')
                ->with('error', 'Failed to load content editor.');
        }
    }

    /**
     * Save/update content with version tracking.
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'locale' => 'nullable|string|max:10',
                'is_published' => 'boolean',
            ]);

            $content = FrontendContent::findOrFail($id);

            // Increment version on update
            $currentVersion = $content->version ?? '1.0';
            $newVersion = $this->incrementVersion($currentVersion);

            $content->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'locale' => $validated['locale'] ?? 'en',
                'is_published' => !empty($validated['is_published']),
                'version' => $newVersion,
                'published_at' => !empty($validated['is_published']) && !$content->published_at
                    ? now()
                    : $content->published_at,
            ]);

            Log::info("Frontend content updated", [
                'content_id' => $content->id,
                'key' => $content->key,
                'version' => $newVersion,
            ]);

            return redirect()->back()->with('success', "Content '{$content->title}' updated (version {$newVersion}).");
        } catch (Exception $e) {
            Log::error('AdminContentController@update failed: ' . $e->getMessage(), [
                'content_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to save content.');
        }
    }

    /**
     * Show form to create new content item.
     */
    public function create()
    {
        try {
            $contentKeys = $this->contentKeys;

            return view('admin.content.create', compact('contentKeys'));
        } catch (Exception $e) {
            Log::error('AdminContentController@create failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.content.index')
                ->with('error', 'Failed to load content creation form.');
        }
    }

    /**
     * Store a new content item.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'key' => 'required|string|max:255|unique:frontend_content,key',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'locale' => 'nullable|string|max:10',
                'is_published' => 'boolean',
            ]);

            $content = FrontendContent::create([
                'key' => $validated['key'],
                'title' => $validated['title'],
                'content' => $validated['content'],
                'locale' => $validated['locale'] ?? 'en',
                'version' => '1.0',
                'is_published' => !empty($validated['is_published']),
                'published_at' => !empty($validated['is_published']) ? now() : null,
            ]);

            Log::info("Frontend content created", [
                'content_id' => $content->id,
                'key' => $content->key,
            ]);

            return redirect()->route('admin.content.index')
                ->with('success', "Content '{$content->title}' created successfully.");
        } catch (Exception $e) {
            Log::error('AdminContentController@store failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to create content.');
        }
    }

    /**
     * Delete a content item.
     */
    public function destroy($id)
    {
        try {
            $content = FrontendContent::findOrFail($id);
            $title = $content->title;

            $content->delete();

            Log::info("Frontend content deleted", [
                'content_id' => $id,
                'key' => $content->key,
            ]);

            return redirect()->route('admin.content.index')
                ->with('success', "Content '{$title}' deleted.");
        } catch (Exception $e) {
            Log::error('AdminContentController@destroy failed: ' . $e->getMessage(), [
                'content_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to delete content.');
        }
    }

    /**
     * Increment version string (e.g., 1.0 -> 1.1, 1.9 -> 2.0).
     */
    private function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);

        if (count($parts) === 2) {
            $major = (int) $parts[0];
            $minor = (int) $parts[1];
            $minor++;

            if ($minor >= 10) {
                $major++;
                $minor = 0;
            }

            return "{$major}.{$minor}";
        }

        // Fallback: append .1
        return $version . '.1';
    }
}
