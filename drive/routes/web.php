<?php

use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// File download routes (require auth or valid share token)
Route::middleware('auth')->get('/drive/download/{file}', [DownloadController::class, 'download'])
    ->name('drive.download');

// Public share link downloads
Route::get('/s/{token}', [DownloadController::class, 'sharedLink'])
    ->name('drive.shared-link');
