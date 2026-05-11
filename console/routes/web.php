<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\SsoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Dashboard (requires authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// SSO Authentication Routes
Route::get('/login/sso', [SsoController::class, 'redirect'])->name('login.sso');
Route::get('/login/callback', [SsoController::class, 'callback'])->name('login.callback');
Route::post('/logout', [SsoController::class, 'logout'])->name('logout');

// Default welcome page
Route::get('/', function () {
    return view('welcome');
})->name('welcome');
