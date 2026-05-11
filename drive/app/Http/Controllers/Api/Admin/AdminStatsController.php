<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DriveFile;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_files' => DriveFile::count(),
            'files_today' => DriveFile::whereDate('created_at', today())->count(),
            'total_storage_used' => User::sum('storage_used'),
            'status' => 'operational',
            'last_update' => now()->toIso8601String(),
        ]);
    }
}
