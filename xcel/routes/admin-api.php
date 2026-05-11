<?php

use Illuminate\Support\Facades\Route;
use App\Models\Spreadsheet;
use App\Models\Template;

// All admin routes require a valid session (web auth) or Sanctum token.
// Registered in bootstrap/app.php with prefix 'api/admin' and 'api' middleware stack.
Route::middleware('auth:sanctum')->group(function () {

Route::get('/admin/stats', function() {
    return response()->json([
        'total_spreadsheets' => Spreadsheet::count(),
        'published' => Spreadsheet::where('status', 'published')->count(),
        'total_templates' => Template::count(),
        'total_charts' => \App\Models\Chart::count(),
        'total_sheets' => \App\Models\Sheet::count(),
        'total_cells' => \App\Models\Cell::count(),
    ]);
});

Route::get('/admin/spreadsheets', function(\Illuminate\Http\Request $req) {
    $query = Spreadsheet::with('user')->withCount(['sheets', 'shares']);
    if ($req->filled('search')) {
        $query->where('title', 'like', "%{$req->search}%");
    }
    if ($req->filled('status')) {
        $query->where('status', $req->status);
    }
    return response()->json(['data' => $query->orderBy('updated_at', 'desc')->paginate(50)->through(function($s) {
        return [
            'id' => $s->id,
            'title' => $s->title,
            'owner_name' => $s->user?->name,
            'status' => $s->status,
            'sheets_count' => $s->sheets_count,
            'updated_at' => $s->updated_at?->format('M d, Y H:i'),
        ];
    })]);
});

Route::get('/admin/spreadsheets/{id}', function($id) {
    return Spreadsheet::with(['user', 'sheets', 'shares'])->findOrFail($id);
});

Route::delete('/admin/spreadsheets/{id}', function($id) {
    Spreadsheet::findOrFail($id)->delete();
    return response()->json(['success' => true]);
});

Route::get('/admin/templates', function() {
    return response()->json(['data' => Template::orderBy('name')->get()]);
});

}); // end auth:sanctum group
