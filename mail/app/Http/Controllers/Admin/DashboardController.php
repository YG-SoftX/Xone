<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mail;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        // Only allow admin users (first user or users with admin flag)
        $isAdmin = $user->id === 1 || in_array($user->email, explode(',', env('ADMIN_EMAILS', 'admin@ygxone.com')));

        if (!$isAdmin) {
            return back()->withErrors(['email' => 'Access denied.']);
        }

        $request->session()->put('admin_user_id', $user->id);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_emails' => Mail::count(),
            'inbox_count' => Mail::where('folder', 'inbox')->count(),
            'sent_count' => Mail::where('folder', 'sent')->count(),
            'trash_count' => Mail::where('folder', 'trash')->count(),
            'attachments_count' => Attachment::count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'pending_jobs' => DB::table('jobs')->count(),
        ];

        $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get();
        $recentMails = Mail::with('user')->orderBy('created_at', 'desc')->take(10)->get();
        $topSenders = Mail::selectRaw('`from`, COUNT(*) as count')
            ->where('folder', 'sent')
            ->groupBy('from')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentMails', 'topSenders'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_user_id');
        $request->session()->regenerate();
        return redirect()->route('admin.login');
    }
}
