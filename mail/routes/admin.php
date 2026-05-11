<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MailConfigController;
use App\Http\Controllers\Admin\QueueController;

Route::prefix('admin')->name('admin.')->group(function () {
    // Login routes (public)
    Route::get('/login', [DashboardController::class, 'showLogin'])->name('login');
    Route::post('/login', [DashboardController::class, 'login']);

    // Protected admin routes
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');

        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{id}/status', [UserController::class, 'updateStatus'])->name('users.status');

        // Mail Config
        Route::get('/config', [MailConfigController::class, 'index'])->name('config');
        Route::post('/config', [MailConfigController::class, 'update'])->name('config.update');
        Route::post('/config/test-imap', [MailConfigController::class, 'testImap'])->name('config.test-imap');

        // Queue
        Route::get('/queue', [QueueController::class, 'index'])->name('queue');
        Route::post('/queue/retry-all', [QueueController::class, 'retryAll'])->name('queue.retry-all');
        Route::delete('/queue/failed/{id}', [QueueController::class, 'destroy'])->name('queue.destroy');
        Route::post('/queue/prune', [QueueController::class, 'prune'])->name('queue.prune');
    });
});
