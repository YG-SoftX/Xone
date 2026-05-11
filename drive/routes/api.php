<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Files
    Route::get('/drive/files', [FileController::class, 'index']);
    Route::post('/drive/files', [FileController::class, 'upload']);
    Route::delete('/drive/files/{file}', [FileController::class, 'destroy']);
    Route::patch('/drive/files/{file}/restore', [FileController::class, 'restore']);
    Route::delete('/drive/files/{file}/force', [FileController::class, 'forceDelete']);
    Route::patch('/drive/files/{file}/star', [FileController::class, 'star']);

    // Folders
    Route::post('/drive/folders', [FolderController::class, 'store']);
    Route::put('/drive/folders/{folder}', [FolderController::class, 'update']);
    Route::delete('/drive/folders/{folder}', [FolderController::class, 'destroy']);
    Route::patch('/drive/folders/{folder}/star', [FolderController::class, 'star']);
});
Route::get('/admin/stats', [\App\Http\Controllers\Api\Admin\AdminStatsController::class, 'index']);