<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

Route::middleware('auth')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/s/{space}', [ChatController::class, 'show'])->name('chat.show');

    // Messages
    Route::post('/s/{space}/send', [ChatController::class, 'send'])->name('chat.send');
    Route::patch('/messages/{message}', [ChatController::class, 'editMessage'])->name('chat.message.edit');
    Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage'])->name('chat.message.delete');
    Route::post('/messages/{message}/react', [ChatController::class, 'react'])->name('chat.message.react');
    Route::get('/messages/{message}/attachment/{index}', [ChatController::class, 'downloadAttachment'])->name('chat.attachment');

    // Spaces
    Route::post('/spaces', [ChatController::class, 'createSpace'])->name('chat.spaces.store');
    Route::post('/dm', [ChatController::class, 'startDm'])->name('chat.dm');

    // Polling — throttled to prevent hammering (120 requests/minute per user)
    Route::get('/s/{space}/poll', [ChatController::class, 'poll'])
        ->middleware('throttle:120,1')
        ->name('chat.poll');

    // Typing indicator — throttled (60/minute)
    Route::post('/s/{space}/typing', [ChatController::class, 'typing'])
        ->middleware('throttle:60,1')
        ->name('chat.typing');

    // Neural Summary — throttled (10/minute, expensive AI call)
    Route::get('/s/{space}/neural-summary', [ChatController::class, 'neuralSummary'])
        ->middleware('throttle:10,1')
        ->name('chat.neural.summary');

    // Channel Ad
    Route::get('/s/{space}/channel-ad', [ChatController::class, 'channelAd'])->name('chat.channel.ad');
});
