<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Main routes
    Route::get('/', [NoteController::class, 'index'])->name('notes.index');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
    
    // Pin and archive
    Route::post('/notes/{note}/pin', [NoteController::class, 'pin'])->name('notes.pin');
    Route::post('/notes/{note}/archive', [NoteController::class, 'archive'])->name('notes.archive');
    
    // Archived and trash views
    Route::get('/archived', [NoteController::class, 'archived'])->name('notes.archived');
    Route::get('/trash', [NoteController::class, 'trash'])->name('notes.trash');
    Route::post('/notes/{id}/restore', [NoteController::class, 'restore'])->name('notes.restore');
    Route::delete('/notes/{id}/permanent', [NoteController::class, 'deletePermanently'])->name('notes.permanent-delete');
    
    // Checklist operations
    Route::post('/notes/{note}/checklist', [NoteController::class, 'addChecklistItem'])->name('notes.checklist.add');
    Route::post('/checklist/{item}/toggle', [NoteController::class, 'toggleChecklistItem'])->name('notes.checklist.toggle');
    Route::delete('/checklist/{item}', [NoteController::class, 'deleteChecklistItem'])->name('notes.checklist.delete');
    
    // Polling for real-time updates
    Route::get('/poll', [NoteController::class, 'poll'])->name('notes.poll');

    // AI & Intelligence
    Route::get('/notes/{note}/summarize', [NoteController::class, 'summarize'])->name('notes.summarize');
    
    // Cross-Ecosystem Publishing
    Route::post('/notes/{note}/publish', [NoteController::class, 'publishToJournal'])->name('notes.publish');
});
