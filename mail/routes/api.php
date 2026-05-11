<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/admin/stats', [\App\Http\Controllers\Api\Admin\AdminStatsController::class, 'index']);

// AI-powered smart reply endpoint
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/mail/{id}/smart-reply', [MailController::class, 'getSmartReply']);
});
