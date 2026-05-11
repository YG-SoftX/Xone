<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation; // Assuming Invitation model exists
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class InviteController extends Controller
{
    /**
     * Display the Sovereign Invitation Hub
     */
    public function index()
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Imperial recruitment access denied.');
        }

        return view('admin.recruitment.invites');
    }

    /**
     * Generate an Elite Sovereign Invitation
     */
    public function generate(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') abort(403);

        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'reward_tier' => 'required|in:platinum,gold,silver',
        ]);

        $code = 'YG-' . strtoupper(Str::random(8));
        
        // Logic to save invitation and its perks
        /*
        Invitation::create([
            'code' => $code,
            'recipient_name' => $request->recipient_name,
            'reward_tier' => $request->reward_tier,
            'expires_at' => now()->addDays(7),
        ]);
        */

        return back()->with('success', "Sovereign Invitation Code '{$code}' has been generated for {$request->recipient_name}.");
    }
}
