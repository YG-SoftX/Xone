<?php

// Add to YG Mail routes/api.php
use Illuminate\Support\Facades\Route;
use App\Models\Mail;
use App\Models\User;
use App\Models\Attachment;

Route::get('/admin/stats', function() {
    if (!session('admin_user_id') && !request()->header('X-Admin-Secret')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    return response()->json([
        'total_emails' => Mail::count(),
        'sent_today' => Mail::whereDate('created_at', today())->count(),
        'failed_jobs' => \DB::table('failed_jobs')->count(),
        'total_users' => User::count(),
        'smtp_accounts' => \App\Models\SmtpAccount::where('is_active', true)->count(),
        'attachments_count' => Attachment::count(),
        'storage_used' => Mail::sum('body'), // approximate
    ]);
});

Route::get('/admin/emails/recent', function() {
    return Mail::with('attachments')->orderBy('created_at', 'desc')->limit(20)->get()->map(function($m) {
        return [
            'id' => $m->id,
            'subject' => $m->subject,
            'from' => $m->from,
            'to' => $m->to,
            'created_at' => $m->created_at?->format('M d, Y H:i'),
            'folder' => $m->folder,
            'is_read' => $m->is_read,
        ];
    });
});

Route::get('/admin/top-senders', function() {
    return Mail::selectRaw('from, COUNT(*) as count')
        ->groupBy('from')
        ->orderByDesc('count')
        ->limit(10)
        ->get()
        ->map(fn($r) => ['email' => $r->from, 'count' => $r->count]);
});

Route::get('/admin/emails', function(\Illuminate\Http\Request $req) {
    $query = Mail::with('attachments');
    if ($req->filled('search')) {
        $search = $req->search;
        $query->where(function($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
              ->orWhere('body', 'like', "%{$search}%");
        });
    }
    return response()->json(['data' => $query->orderBy('created_at', 'desc')->paginate(50)]);
});

Route::get('/admin/emails/{id}', function($id) {
    $email = Mail::with('attachments')->findOrFail($id);
    return response()->json($email);
});

Route::delete('/admin/emails/{id}', function($id) {
    Mail::findOrFail($id)->delete();
    return response()->json(['success' => true]);
});
