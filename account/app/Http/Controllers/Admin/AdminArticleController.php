<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class AdminArticleController extends Controller
{
    /**
     * Available article categories.
     */
    private array $categories = ['news', 'tutorial', 'announcement', 'blog'];

    /**
     * List all articles with filters.
     */
    public function index(Request $request)
    {
        try {
            $query = Article::with('author');

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

            // Filter by author
            if ($request->filled('author_id')) {
                $query->where('author_id', $request->author_id);
            }

            // Search by title or excerpt
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->get('sort', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $allowedSorts = ['created_at', 'updated_at', 'published_at', 'title', 'views'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $articles = $query->paginate(50);

            $authors = User::orderBy('name')->get(['id', 'name', 'email']);

            $stats = [
                'total' => Article::count(),
                'published' => Article::where('is_published', true)->count(),
                'drafts' => Article::where('is_published', false)->count(),
                'total_views' => Article::sum('views'),
                'by_category' => Article::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
            ];

            return view('admin.articles.index', compact('articles', 'authors', 'stats'));
        } catch (Exception $e) {
            Log::error('AdminArticleController@index failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to load articles.');
        }
    }

    /**
     * Show form to create a new article.
     */
    public function create()
    {
        try {
            $categories = $this->categories;
            $authors = User::orderBy('name')->get(['id', 'name', 'email']);

            return view('admin.articles.create', compact('categories', 'authors'));
        } catch (Exception $e) {
            Log::error('AdminArticleController@create failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.articles.index')
                ->with('error', 'Failed to load article creation form.');
        }
    }

    /**
     * Store a new article with optional featured image upload.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'author_id' => 'nullable|exists:users,id',
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:articles,slug',
                'excerpt' => 'nullable|string',
                'content' => 'required|string',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'category' => 'required|in:news,tutorial,announcement,blog',
                'tags' => 'nullable|string',
                'is_published' => 'boolean',
            ]);

            // Auto-generate slug if not provided
            $slug = $validated['slug'] ?? Str::slug($validated['title']);

            // Ensure slug is unique
            $originalSlug = $slug;
            $counter = 1;
            while (Article::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Handle featured image upload
            $featuredImagePath = null;
            if ($request->hasFile('featured_image')) {
                $file = $request->file('featured_image');
                $fileName = 'articles/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public', $fileName);
                $featuredImagePath = '/storage/' . $fileName;
            }

            // Parse tags from comma-separated string
            $tags = null;
            if (!empty($validated['tags'])) {
                $tags = array_map('trim', explode(',', $validated['tags']));
            }

            $article = Article::create([
                'author_id' => $validated['author_id'] ?? session('admin_user_id'),
                'slug' => $slug,
                'title' => $validated['title'],
                'excerpt' => $validated['excerpt'] ?? null,
                'content' => $validated['content'],
                'featured_image' => $featuredImagePath,
                'category' => $validated['category'],
                'tags' => $tags,
                'is_published' => !empty($validated['is_published']),
                'published_at' => !empty($validated['is_published']) ? Carbon::now() : null,
            ]);

            Log::info("Article created", [
                'article_id' => $article->id,
                'slug' => $article->slug,
                'category' => $article->category,
            ]);

            return redirect()->route('admin.articles.index')
                ->with('success', "Article '{$article->title}' created successfully.");
        } catch (Exception $e) {
            Log::error('AdminArticleController@store failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to create article.');
        }
    }

    /**
     * Show form to edit an article.
     */
    public function edit($id)
    {
        try {
            $article = Article::findOrFail($id);
            $categories = $this->categories;
            $authors = User::orderBy('name')->get(['id', 'name', 'email']);

            return view('admin.articles.edit', compact('article', 'categories', 'authors'));
        } catch (Exception $e) {
            Log::error('AdminArticleController@edit failed: ' . $e->getMessage(), [
                'article_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.articles.index')
                ->with('error', 'Failed to load article editor.');
        }
    }

    /**
     * Update an article.
     */
    public function update(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);

            $validated = $request->validate([
                'author_id' => 'nullable|exists:users,id',
                'title' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:articles,slug,' . $id,
                'excerpt' => 'nullable|string',
                'content' => 'required|string',
                'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'remove_featured_image' => 'boolean',
                'category' => 'required|in:news,tutorial,announcement,blog',
                'tags' => 'nullable|string',
                'is_published' => 'boolean',
            ]);

            // Handle featured image removal
            if (!empty($validated['remove_featured_image'])) {
                $article->featured_image = null;
            }

            // Handle new featured image upload
            if ($request->hasFile('featured_image')) {
                $file = $request->file('featured_image');
                $fileName = 'articles/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public', $fileName);
                $article->featured_image = '/storage/' . $fileName;
            }

            // Parse tags from comma-separated string
            $tags = $article->tags;
            if (array_key_exists('tags', $validated)) {
                $tags = !empty($validated['tags'])
                    ? array_map('trim', explode(',', $validated['tags']))
                    : null;
            }

            $article->update([
                'author_id' => $validated['author_id'] ?? $article->author_id,
                'slug' => $validated['slug'] ?? $article->slug,
                'title' => $validated['title'],
                'excerpt' => $validated['excerpt'] ?? $article->excerpt,
                'content' => $validated['content'],
                'featured_image' => $article->featured_image,
                'category' => $validated['category'],
                'tags' => $tags,
                'is_published' => !empty($validated['is_published']),
            ]);

            // Set published_at if publishing for the first time
            if (!empty($validated['is_published']) && !$article->published_at) {
                $article->update(['published_at' => Carbon::now()]);
            }

            Log::info("Article updated", [
                'article_id' => $article->id,
                'slug' => $article->slug,
            ]);

            return redirect()->back()->with('success', "Article '{$article->title}' updated successfully.");
        } catch (Exception $e) {
            Log::error('AdminArticleController@update failed: ' . $e->getMessage(), [
                'article_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to update article.');
        }
    }

    /**
     * Publish an article (set published_at).
     */
    public function publish($id)
    {
        try {
            $article = Article::findOrFail($id);

            $article->update([
                'is_published' => true,
                'published_at' => $article->published_at ?? Carbon::now(),
            ]);

            Log::info("Article published", [
                'article_id' => $article->id,
                'title' => $article->title,
            ]);

            return redirect()->back()->with('success', "Article '{$article->title}' published.");
        } catch (Exception $e) {
            Log::error('AdminArticleController@publish failed: ' . $e->getMessage(), [
                'article_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to publish article.');
        }
    }

    /**
     * Unpublish an article.
     */
    public function unpublish($id)
    {
        try {
            $article = Article::findOrFail($id);

            $article->update([
                'is_published' => false,
            ]);

            Log::info("Article unpublished", [
                'article_id' => $article->id,
                'title' => $article->title,
            ]);

            return redirect()->back()->with('success', "Article '{$article->title}' unpublished.");
        } catch (Exception $e) {
            Log::error('AdminArticleController@unpublish failed: ' . $e->getMessage(), [
                'article_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to unpublish article.');
        }
    }

    /**
     * Delete an article.
     */
    public function destroy($id)
    {
        try {
            $article = Article::findOrFail($id);
            $title = $article->title;

            $article->delete();

            Log::info("Article deleted", [
                'article_id' => $id,
                'title' => $title,
            ]);

            return redirect()->route('admin.articles.index')
                ->with('success', "Article '{$title}' deleted.");
        } catch (Exception $e) {
            Log::error('AdminArticleController@destroy failed: ' . $e->getMessage(), [
                'article_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to delete article.');
        }
    }
}
