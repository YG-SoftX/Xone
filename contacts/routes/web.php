<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

Route::middleware('auth')->group(function () {
    Route::get('/', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/create', [ContactController::class, 'create'])->name('contacts.create');
    Route::post('/', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::get('/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
    Route::put('/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('/{contact}/star', [ContactController::class, 'toggleStar'])->name('contacts.star');

    // Groups
    Route::post('/groups', [ContactController::class, 'createGroup'])->name('contacts.groups.store');
    Route::delete('/groups/{group}', [ContactController::class, 'deleteGroup'])->name('contacts.groups.destroy');

    // Import / Export
    Route::post('/import', [ContactController::class, 'importContacts'])->name('contacts.import');
    Route::get('/export/vcard', [ContactController::class, 'exportVCard'])->name('contacts.export.vcard');
    Route::get('/export/csv', [ContactController::class, 'exportCsv'])->name('contacts.export.csv');

    // API
    Route::get('/api/search', [ContactController::class, 'apiSearch'])->name('contacts.api.search');
});
