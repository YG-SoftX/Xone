<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ElectronAppController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Ecosystem API routes
Route::prefix('ecosystem')->group(function () {
    Route::post('/register', [ElectronAppController::class, 'register']);
    Route::post('/heartbeat', [ElectronAppController::class, 'heartbeat']);
    Route::get('/config/{appId}', [ElectronAppController::class, 'getConfig']);
});