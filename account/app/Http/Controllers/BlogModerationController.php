<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogModerationController extends Controller
{
    /**
     * Display the moderation queue
     */
    public function index()
    {
        // Only Super Admins can access
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Imperial access denied.');
        }

        $pendingPosts = BlogPost::with('author')
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.blog.moderation', compact('pendingPosts'));
    }

    /**
     * Approve a pending post
     */
    public function approve(BlogPost $post)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403);
        }

        $post->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', "Article '{$post->title}' has been officially approved for publication.");
    }

    /**
     * Reject a pending post
     */
    public function reject(BlogPost $post)
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403);
        }

        $post->update(['status' => 'rejected']);

        return back()->with('error', "Article '{$post->title}' has been rejected from the Imperial Journal.");
    }
}
