<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mail;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_mails' => Mail::count(),
            'mails_today' => Mail::whereDate('created_at', today())->count(),
            'unread_mails' => Mail::where('is_read', false)->count(),
            'status' => 'operational',
            'last_update' => now()->toIso8601String(),
        ]);
    }
}
