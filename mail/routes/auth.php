<?php

use Illuminate\Support\Facades\Route;

// All authentication is handled through YG Account SSO
// No local registration, login, or password management needed

Route::middleware('guest')->group(function () {
    // Redirect all auth attempts to YG Account SSO
    Route::get('login', function () {
        return redirect()->route('sso.initiate');
    })->name('login');

    Route::get('register', function () {
        return redirect()->route('sso.initiate');
    })->name('register');
});

Route::middleware('auth')->group(function () {
    // Password management is handled by YG Account
    // Only keep logout functionality
    Route::post('logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
