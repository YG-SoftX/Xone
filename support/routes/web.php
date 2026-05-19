<?php

use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────
Route::get('/login', fn () => view('auth.login'))
    ->name('login')
    ->middleware('guest');

Route::get('/auth/sso/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [SsoController::class, 'logout'])->name('logout');
    Route::get('/', [SupportController::class, 'dashboard'])->name('dashboard');

    // ── Tickets ───────────────────────────────────────────────────────────────
    Route::get('/tickets',                [SupportController::class, 'tickets'])->name('tickets.index');
    Route::get('/tickets/create',         [SupportController::class, 'createTicket'])->name('tickets.create');
    Route::post('/tickets',               [SupportController::class, 'storeTicket'])->name('tickets.store');
    Route::get('/tickets/{id}',           [SupportController::class, 'showTicket'])->name('tickets.show');
    Route::post('/tickets/{id}/reply',    [SupportController::class, 'replyTicket'])->name('tickets.reply');
    Route::post('/tickets/{id}/close',    [SupportController::class, 'closeTicket'])->name('tickets.close');

    // ── Knowledge Base ────────────────────────────────────────────────────────
    Route::get('/knowledge',             [KnowledgeBaseController::class, 'index'])->name('knowledge.index');
    Route::get('/knowledge/search',      [KnowledgeBaseController::class, 'search'])->name('knowledge.search');
    Route::get('/knowledge/category/{category}', [KnowledgeBaseController::class, 'category'])->name('knowledge.category');
    Route::get('/knowledge/{slug}',      [KnowledgeBaseController::class, 'show'])->name('knowledge.show');

    // ── System Status ───────────────────────────────────────────────────────────
    Route::get('/status',                [StatusController::class, 'index'])->name('status.index');
    Route::get('/status/refresh',        [StatusController::class, 'refresh'])->name('status.refresh');
});
