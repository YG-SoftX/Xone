<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

// SSO Initiate (public)
Route::get('/sso/initiate', function (\Illuminate\Http\Request $request) {
    $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
    $callback = url('/sso/callback');
    $service = 'YG Docx';
    $clientId = $request->query('client_id');
    $queryParams = http_build_query([
        'service' => $service,
        'callback' => $callback,
    ]);
    if ($clientId) {
        $queryParams .= '&client_id=' . urlencode($clientId);
    }
    return redirect($accountUrl . '/sso/initiate?' . $queryParams);
})->name('sso.initiate');

// SSO Callback (public)
Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Document Home
    Route::get('/', [DocumentController::class, 'index'])->name('home');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{id}', [DocumentController::class, 'show'])->name('documents.show');
    Route::patch('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');
    Route::post('/documents/{id}/duplicate', [DocumentController::class, 'duplicate'])->name('documents.duplicate');
    Route::post('/documents/{document}/move', [DocumentController::class, 'moveToFolder'])->name('documents.move');
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->name('documents.archive');
    Route::post('/documents/{document}/restore', [DocumentController::class, 'restore'])->name('documents.restore');
    Route::post('/documents/{document}/publish', [DocumentController::class, 'publishToJournal'])->name('documents.publish');
    Route::delete('/documents/{document}', [DocumentController::class, 'delete'])->name('documents.delete');

    // Folders
    Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('/folders/{id}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{id}', [FolderController::class, 'delete'])->name('folders.delete');

    // Sharing
    Route::get('/documents/{id}/shares', [ShareController::class, 'index'])->name('shares.index');
    Route::post('/documents/{id}/share', [ShareController::class, 'store'])->name('shares.store');
    Route::patch('/shares/{id}', [ShareController::class, 'update'])->name('shares.update');
    Route::delete('/shares/{id}', [ShareController::class, 'revoke'])->name('shares.revoke');

    // Comments
    Route::get('/documents/{id}/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/documents/{id}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::patch('/comments/{id}', [CommentController::class, 'update'])->name('comments.update');
    Route::post('/comments/{id}/resolve', [CommentController::class, 'resolve'])->name('comments.resolve');
    Route::delete('/comments/{id}', [CommentController::class, 'delete'])->name('comments.delete');

    // Suggestions
    Route::get('/documents/{id}/suggestions', [SuggestionController::class, 'index'])->name('suggestions.index');
    Route::post('/documents/{id}/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store');
    Route::post('/suggestions/{id}/accept', [SuggestionController::class, 'accept'])->name('suggestions.accept');
    Route::post('/suggestions/{id}/reject', [SuggestionController::class, 'reject'])->name('suggestions.reject');

    // Versions
    Route::get('/documents/{id}/versions', [VersionController::class, 'index'])->name('versions.index');
    Route::get('/documents/{id}/versions/{versionId}', [VersionController::class, 'show'])->name('versions.show');
    Route::post('/documents/{id}/versions/{versionId}/restore', [VersionController::class, 'restore'])->name('versions.restore');
    Route::delete('/documents/{id}/versions/{versionId}', [VersionController::class, 'delete'])->name('versions.delete');

    // Templates
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::post('/templates/{id}/apply/{documentId}', [TemplateController::class, 'apply'])->name('templates.apply');
    Route::delete('/templates/{id}', [TemplateController::class, 'delete'])->name('templates.delete');

    // Export
    Route::get('/documents/{id}/export/{format}', [ExportController::class, 'export'])->name('documents.export');

    // Advanced Features (MS Word Level)
    Route::prefix('documents/{document}')->group(function () {
        // Presence tracking for real-time collaboration
        Route::get('/presence', [\App\Http\Controllers\AdvancedDocumentController::class, 'getPresence'])->name('documents.presence');
        Route::post('/presence', [\App\Http\Controllers\AdvancedDocumentController::class, 'updatePresence'])->name('documents.presence.update');
        
        // Bookmarks
        Route::get('/bookmarks', [\App\Http\Controllers\AdvancedDocumentController::class, 'getBookmarks'])->name('documents.bookmarks');
        Route::post('/bookmarks', [\App\Http\Controllers\AdvancedDocumentController::class, 'createBookmark'])->name('documents.bookmarks.create');
        
        // Footnotes & Endnotes
        Route::post('/notes', [\App\Http\Controllers\AdvancedDocumentController::class, 'createNote'])->name('documents.notes.create');
        
        // Shapes & Drawings
        Route::post('/shapes', [\App\Http\Controllers\AdvancedDocumentController::class, 'insertShape'])->name('documents.shapes.insert');
        
        // Charts
        Route::post('/charts', [\App\Http\Controllers\AdvancedDocumentController::class, 'insertChart'])->name('documents.charts.insert');
        
        // Document Comparison
        Route::post('/compare', [\App\Http\Controllers\AdvancedDocumentController::class, 'compareDocuments'])->name('documents.compare');
        
        // Macros
        Route::get('/macros', [\App\Http\Controllers\AdvancedDocumentController::class, 'getMacros'])->name('documents.macros');
        Route::post('/macros', [\App\Http\Controllers\AdvancedDocumentController::class, 'saveMacro'])->name('documents.macros.save');
        Route::post('/macros/{macroId}/execute', [\App\Http\Controllers\AdvancedDocumentController::class, 'executeMacro'])->name('documents.macros.execute');
    });
});
