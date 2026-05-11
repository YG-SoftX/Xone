<?php

use App\Http\Controllers\SsoController;
use App\Http\Controllers\FormBuilderController;
use Illuminate\Support\Facades\Route;

// SSO Bridge
Route::get('/sso/initiate', [SsoController::class, 'initiate'])->name('login');
Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

// Public Forms (No Auth Required)
Route::get('/f/{slug}', [FormBuilderController::class, 'showPublic'])->name('collect.public');
Route::post('/f/{slug}', [FormBuilderController::class, 'submit'])->name('collect.submit');

// Sovereign Form Builder (Auth Required)
Route::middleware('auth')->group(function () {
    Route::get('/', [FormBuilderController::class, 'index'])->name('collect.index');
    Route::get('/create', [FormBuilderController::class, 'create'])->name('collect.create');
    Route::post('/store', [FormBuilderController::class, 'store'])->name('collect.store');
    
    // Form Management & Results
    Route::get('/form/{form}/results', [FormBuilderController::class, 'results'])->name('collect.results');
    Route::get('/form/{form}/export', [FormBuilderController::class, 'exportCsv'])->name('collect.export');
    Route::delete('/form/{form}', [FormBuilderController::class, 'destroy'])->name('collect.destroy');
});
