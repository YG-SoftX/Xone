<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_documents' => Document::count(),
            'docs_today' => Document::whereDate('created_at', today())->count(),
            'status' => 'operational',
            'last_update' => now()->toIso8601String(),
        ]);
    }
}
