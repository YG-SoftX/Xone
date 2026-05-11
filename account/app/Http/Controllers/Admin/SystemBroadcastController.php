<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Notification; // Assuming Notification model exists
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemBroadcastController extends Controller
{
    /**
     * Display the Imperial Proclamation Portal
     */
    public function index()
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Imperial proclamation access denied.');
        }

        return view('admin.system.broadcast');
    }

    /**
     * Send a Platform-Wide Proclamation
     */
    public function broadcast(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:proclamation,update,security,announcement',
        ]);

        // Logic to send notification to ALL users in the empire
        // In production, this would be a queued job for millions of users
        /*
        User::chunk(1000, function ($users) use ($request) {
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                    'is_read' => false,
                ]);
            }
        });
        */

        return back()->with('success', "The Imperial Proclamation '{$request->title}' has been broadcasted to all citizens of YG Xone.");
    }
}
