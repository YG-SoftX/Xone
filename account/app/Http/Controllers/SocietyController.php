<?php

namespace App\Http\Controllers;

use App\Models\Society\Post;
use App\Models\Society\Comment;
use App\Models\Society\Like;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SocietyController extends Controller
{
    /**
     * Display the Society Feed
     */
    public function index(Request $request)
    {
        $query = Post::with(['user', 'comments.user', 'likes'])
            ->where('is_public', true)
            ->where('is_hidden', false);

        // Trending Algorithm Filter
        if ($request->filter === 'trending') {
            $query->orderBy('trending_score', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $posts = $query->paginate(15);
        $leaderboard = \App\Models\User::orderBy('stones', 'desc')->limit(5)->get();

        return view('society.index', compact('posts', 'leaderboard'));
    }

    /**
     * Store a new Post (Media + Content)
     */
    public function store(Request $request)
    {
        if (Auth::user()->is_society_banned) {
            return back()->withErrors(['error' => 'Your social privileges have been suspended.']);
        }

        $request->validate([
            'content' => 'required_without:media|string|max:5000',
            'media' => 'nullable|file|max:51200',
            'flair' => 'nullable|string|max:20',
        ]);

        // ==========================================
        // THE YUGA SENTINEL (AI Moderation)
        // ==========================================
        $yuga = app(\App\Services\YugaService::class);
        $safetyVerdict = $yuga->analyzeContent($request->input('content'), [
            'flair' => $request->flair,
            'user_rank' => Auth::user()->rank,
        ]);

        $isFlagged = !$safetyVerdict['safe'];
        $moderationReason = $safetyVerdict['reason'] ?? 'Flagged by Yuga 1.0';

        $mediaUrl = null;
        $mediaType = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = $file->getMimeType();
            
            // Neural Visual Moderation (Yuga 1.0 Vision Sentinel)
            $mediaVerdict = $yuga->moderateMedia($file);
            if (!$mediaVerdict['safe']) {
                $isFlagged = true;
                $moderationReason = $mediaVerdict['reason'] ?? 'Inappropriate Media detected';
            }

            if (str_starts_with($mime, 'image/')) $mediaType = 'image';
            elseif (str_starts_with($mime, 'video/')) $mediaType = 'video';
            else $mediaType = 'file';

            $mediaUrl = $file->store('society/media', 'public');
        }

        $post = Post::create([
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'flair' => $request->flair,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'is_hidden' => $isFlagged,
            'is_public' => !$isFlagged,
        ]);

        // Log Initial RL Baseline
        \App\Models\Society\RLFeedback::logSignal(
            $post->id, 
            $isFlagged ? -0.1 : 0.1, 
            'initial_verdict', 
            $safetyVerdict['confidence'] ?? 1.0
        );

        if ($isFlagged) {
            ActivityLog::record(Auth::id(), "Yuga 1.0 Blocked Content: {$moderationReason}", 'Security', '🤖', 'YG Society');
            return back()->with('warning', "Yuga 1.0 Intelligence has flagged this content: {$moderationReason}. It is currently in quarantine.");
        }

        // Reward: The Creator (+10 Stones)
        Auth::user()->increment('stones', 10);

        ActivityLog::record(Auth::id(), "Shared a new {$mediaType} post and earned 10 Stones", 'Social', '🌐', 'YG Society');

        return back()->with('success', 'Your post is now live! +10 Stones earned.');
    }

    /**
     * Toggle Like on a Post
     */
    public function toggleLike(Post $post)
    {
        $post->loadMissing('user');
        $like = Like::where('user_id', Auth::id())->where('post_id', $post->id)->first();

        if ($like) {
            $like->delete();
            $post->decrement('likes_count');
            $post->user->decrement('stones', 2);
            $post->updateTrendingScore(); // Sync score
            
            ActivityLog::record(Auth::id(), "Removed an Echo from a post", 'Social', '💔', 'YG Society');
            return response()->json(['status' => 'unliked', 'count' => $post->likes_count]);
        }

        Like::create([
            'user_id' => Auth::id(),
            'post_id' => $post->id,
        ]);
        $post->increment('likes_count');
        $post->user->increment('stones', 2);
        $post->updateTrendingScore(); // Sync score

        ActivityLog::record(Auth::id(), "Echoed a post by {$post->user->name}", 'Social', '❤️', 'YG Society');

        return response()->json(['status' => 'liked', 'count' => $post->likes_count]);
    }

    /**
     * Report Content
     */
    public function report(Request $request)
    {
        $request->validate([
            'reportable_id' => 'required|integer',
            'reportable_type' => 'required|string',
            'reason' => 'required|string|max:100',
        ]);

        \App\Models\Society\Report::create([
            'user_id' => Auth::id(),
            'reportable_id' => $request->reportable_id,
            'reportable_type' => $request->reportable_type,
            'reason' => $request->reason,
            'details' => $request->details,
        ]);

        // RL Penalty: Community Objection (-0.5)
        if ($request->reportable_type === 'App\\Models\\Society\\Post') {
            \App\Models\Society\RLFeedback::logSignal($request->reportable_id, -0.5, 'community_report');
        }

        ActivityLog::record(Auth::id(), "Signaled Guardians (Reported content)", 'Security', '🚨', 'YG Society');

        return response()->json(['success' => true, 'message' => 'Report submitted. Our guardians will review it shortly.']);
    }

    /**
     * Add a Comment to a Post
     */
    public function addComment(Request $request, Post $post)
    {
        $request->validate(['content' => 'required|string|max:1000']);

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'post_id' => $post->id,
            'parent_id' => $request->parent_id,
            'content' => $request->input('content'),
        ]);

        $post->increment('comments_count');

        // Reward: The Conversationalist (+5 Stones)
        Auth::user()->increment('stones', 5);

        return back()->with('success', 'Comment added! +5 Stones earned.');
    }

    /**
     * Transfer Stones to another User
     */
    public function transferStones(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1',
            'note' => 'nullable|string|max:100',
        ]);

        $sender = Auth::user();
        $recipient = \App\Models\User::find($request->recipient_id);

        if ($sender->id == $recipient->id) {
            return response()->json(['success' => false, 'message' => 'You cannot reward yourself!']);
        }

        if ($sender->stones < $request->amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient Stone balance.']);
        }

        // Transaction
        $sender->decrement('stones', $request->amount);
        $recipient->increment('stones', $request->amount);

        // Logging
        ActivityLog::record($sender->id, "Sent {$request->amount} Stones to {$recipient->name}", 'Social', '💎', 'YG Society');
        ActivityLog::record($recipient->id, "Received {$request->amount} Stones from {$sender->name}", 'Social', '💎', 'YG Society');

        return response()->json([
            'success' => true,
            'message' => "Successfully gifted {$request->amount} Stones!",
            'new_balance' => $sender->stones
        ]);
    }

    /**
     * Delete a Post (Guardian Reversal)
     */
    public function destroy(Post $post)
    {
        if ($post->user_id !== Auth::id() && Auth::user()->role !== 'admin' && Auth::user()->role !== 'super_admin') {
            abort(403);
        }

        // RL Penalty: Guardian Reversal (-1.0)
        // This is only logged if an admin deletes a post that wasn't already hidden
        if (Auth::user()->role === 'admin' || Auth::user()->role === 'super_admin') {
            \App\Models\Society\RLFeedback::logSignal($post->id, -1.0, 'admin_reversal');
        }

        if ($post->media_url) {
            \Storage::disk('public')->delete($post->media_url);
        }

        $post->delete();

        return back()->with('success', 'Post deleted and intelligence updated.');
    }
}
