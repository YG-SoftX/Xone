<?php

use App\Http\Controllers\SpreadsheetController;
use App\Http\Controllers\SheetController;
use App\Http\Controllers\CellController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

// SSO Initiate (public)
Route::get('/sso/initiate', function (\Illuminate\Http\Request $request) {
    $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
    $callback = url('/sso/callback');
    $service = 'YG Xcel';
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
    // Spreadsheets
    Route::get('/', [SpreadsheetController::class, 'index'])->name('home');
    Route::get('/create', [SpreadsheetController::class, 'create'])->name('spreadsheets.create');
    Route::post('/', [SpreadsheetController::class, 'store'])->name('spreadsheets.store');
    Route::get('/{id}', [SpreadsheetController::class, 'show'])->name('spreadsheets.show');
    Route::patch('/{id}', [SpreadsheetController::class, 'update'])->name('spreadsheets.update');
    Route::post('/{id}/duplicate', [SpreadsheetController::class, 'duplicate'])->name('spreadsheets.duplicate');
    Route::post('/{id}/archive', [SpreadsheetController::class, 'archive'])->name('spreadsheets.archive');
    Route::post('/{id}/restore', [SpreadsheetController::class, 'restore'])->name('spreadsheets.restore');
    Route::delete('/{id}', [SpreadsheetController::class, 'delete'])->name('spreadsheets.delete');
    
    // Imperial Revenue API for Formulas
    Route::get('/api/imperial-revenue', [SpreadsheetController::class, 'imperialRevenue'])->name('spreadsheets.imperial.revenue');

    // Sheets
    Route::post('/spreadsheets/{spreadsheetId}/sheets', [SheetController::class, 'store'])->name('sheets.store');
    Route::patch('/sheets/{id}', [SheetController::class, 'update'])->name('sheets.update');
    Route::post('/sheets/{id}/duplicate', [SheetController::class, 'duplicate'])->name('sheets.duplicate');
    Route::delete('/sheets/{id}', [SheetController::class, 'delete'])->name('sheets.delete');

    // Cells
    Route::post('/sheets/{sheetId}/cells/batch', [CellController::class, 'batchUpdate'])->name('cells.batch');
    Route::get('/cells/{id}', [CellController::class, 'getCell'])->name('cells.get');
    Route::patch('/cells/{id}', [CellController::class, 'updateSingle'])->name('cells.update');
    Route::get('/sheets/{sheetId}/range/{start}:{end}', [CellController::class, 'getRange'])->name('cells.range');
    Route::delete('/sheets/{sheetId}/cells/clear', [CellController::class, 'clearCells'])->name('cells.clear');

    // Charts
    Route::get('/sheets/{sheetId}/charts', [ChartController::class, 'list'])->name('charts.index');
    Route::post('/sheets/{sheetId}/charts', [ChartController::class, 'store'])->name('charts.store');
    Route::patch('/charts/{id}', [ChartController::class, 'update'])->name('charts.update');
    Route::delete('/charts/{id}', [ChartController::class, 'delete'])->name('charts.delete');

    // Sharing
    Route::get('/spreadsheets/{id}/shares', [ShareController::class, 'index'])->name('shares.index');
    Route::post('/spreadsheets/{id}/share', [ShareController::class, 'store'])->name('shares.store');
    Route::patch('/shares/{id}', [ShareController::class, 'update'])->name('shares.update');
    Route::delete('/shares/{id}', [ShareController::class, 'revoke'])->name('shares.revoke');

    // Comments
    Route::get('/sheets/{sheetId}/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/sheets/{sheetId}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::patch('/comments/{id}', [CommentController::class, 'update'])->name('comments.update');
    Route::post('/comments/{id}/resolve', [CommentController::class, 'resolve'])->name('comments.resolve');
    Route::delete('/comments/{id}', [CommentController::class, 'delete'])->name('comments.delete');

    // Export
    Route::get('/spreadsheets/{id}/export/{format}', [ExportController::class, 'export'])->name('spreadsheets.export');

    // Templates
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::post('/templates/{id}/apply', [TemplateController::class, 'apply'])->name('templates.apply');
    Route::delete('/templates/{id}', [TemplateController::class, 'delete'])->name('templates.delete');
});
