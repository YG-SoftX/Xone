<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Laravel's built-in auth scaffolding. Provides:
|   GET /login      → show login form
|   POST /login     → process credentials
|   POST /logout    → log out
|   (optional) register, password reset, etc.
|
| Note: The home module uses SSO for authentication, so we disable
| registration and password reset routes to avoid conflicts.
|
*/

// Auth::routes() is removed because laravel/ui is not installed.
// We map the standard logout route to our SSO controller.
Route::post('/logout', [\App\Http\Controllers\SSOController::class, 'logout'])->name('logout');
