<?php

namespace App\Http\Controllers;

use App\Models\Documentation;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * Display the Help Center landing page.
     */
    public function index()
    {
        $categories = [
            'account' => 'YG Account',
            'mail'    => 'YG Mail',
            'pay'     => 'YG Pay',
            'drive'   => 'YG Drive',
            'meet'    => 'YG Meet',
            'docx'    => 'YG Docx',
            'playstore' => 'YG Playstore',
            'security' => 'Privacy & Safety'
        ];

        return view('support.index', compact('categories'));
    }

    /**
     * Display articles for a specific category.
     */
    public function category($category)
    {
        $articles = Documentation::where('category', $category)
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

        return view('support.category', compact('articles', 'category'));
    }

    /**
     * Display a single help article.
     */
    public function show($slug)
    {
        $article = Documentation::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Get related articles in same category
        $related = Documentation::where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->limit(5)
            ->get();

        return view('support.article', compact('article', 'related'));
    }

    /**
     * Handle help search.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $results = Documentation::search($query)
            ->where('is_published', true)
            ->limit(10)
            ->get();

        return view('support.search', compact('results', 'query'));
    }
}
