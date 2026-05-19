<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    /**
     * Knowledge base home — show categories and featured articles.
     */
    public function index(): View
    {
        $categories = Article::categories();

        $featured = Article::published()
            ->articles()
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $faqs = Article::published()
            ->faq()
            ->orderBy('sort_order')
            ->get();

        $articlesByCategory = [];
        foreach (array_keys($categories) as $cat) {
            $articlesByCategory[$cat] = Article::published()
                ->articles()
                ->where('category', $cat)
                ->orderBy('sort_order')
                ->limit(5)
                ->get();
        }

        return view('knowledge_base.index', compact('categories', 'featured', 'faqs', 'articlesByCategory'));
    }

    /**
     * Show articles in a specific category.
     */
    public function category(string $category): View
    {
        $categories = Article::categories();

        abort_unless(isset($categories[$category]), 404);

        $articles = Article::published()
            ->articles()
            ->where('category', $category)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $faqs = Article::published()
            ->faq()
            ->where('category', $category)
            ->orderBy('sort_order')
            ->get();

        return view('knowledge_base.category', compact('categories', 'articles', 'faqs', 'category'));
    }

    /**
     * Show a single article.
     */
    public function show(string $slug): View
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();

        $related = Article::published()
            ->articles()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        return view('knowledge_base.show', compact('article', 'related'));
    }

    /**
     * Search knowledge base articles.
     */
    public function search(Request $request): View
    {
        $query = $request->input('q');

        $articles = Article::published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->orderBy('sort_order')
            ->paginate(20);

        return view('knowledge_base.search', compact('articles', 'query'));
    }
}
