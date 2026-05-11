<?php

// YG DocX admin API routes - add to routes/api.php or create as separate file
use Illuminate\Support\Facades\Route;
use App\Models\Document;
use App\Models\Template;

Route::get('/admin/stats', function() {
    return response()->json([
        'total_documents' => Document::count(),
        'published' => Document::where('status', 'published')->count(),
        'shared_documents' => \App\Models\DocumentShare::count(),
        'total_templates' => Template::count(),
        'total_comments' => \App\Models\DocumentComment::count(),
        'total_versions' => \App\Models\DocumentVersion::count(),
    ]);
});

Route::get('/admin/documents', function(\Illuminate\Http\Request $req) {
    $query = Document::with('user')->withCount(['sheets', 'shares']);
    if ($req->filled('search')) {
        $query->where('title', 'like', "%{$req->search}%");
    }
    if ($req->filled('status')) {
        $query->where('status', $req->status);
    }
    return response()->json(['data' => $query->orderBy('updated_at', 'desc')->paginate(50)->through(function($d) {
        return [
            'id' => $d->id,
            'title' => $d->title,
            'owner_name' => $d->user?->name,
            'status' => $d->status,
            'sheets_count' => $d->sheets_count,
            'updated_at' => $d->updated_at?->format('M d, Y H:i'),
        ];
    })]);
});

Route::get('/admin/documents/{id}', function($id) {
    return Document::with(['user', 'sheets', 'shares', 'comments'])->findOrFail($id);
});

Route::delete('/admin/documents/{id}', function($id) {
    Document::findOrFail($id)->delete();
    return response()->json(['success' => true]);
});

Route::get('/admin/templates', function() {
    return response()->json(['data' => Template::orderBy('name')->get()]);
});
