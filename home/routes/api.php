<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIProxyController;

/*
|--------------------------------------------------------------------------
| AI Proxy API Routes
|--------------------------------------------------------------------------
| Expose a thin wrapper around the internal YG AI service. Rate limiting
| protects the endpoint from abuse while allowing any client (including
| external users) to perform searches.
|--------------------------------------------------------------------------
*/

Route::post('/api/ai/search', [AIProxyController::class, 'search'])
    ->middleware(['throttle:60,1']); // 60 requests per minute per IP
