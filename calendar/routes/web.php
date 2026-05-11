<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

// SSO (public) — throttled to prevent token brute-force
Route::get('/sso/callback', [SsoController::class, 'callback'])
    ->middleware('throttle:10,1')
    ->name('sso.callback');

Route::middleware('auth')->group(function () {

    // Main calendar
    Route::get('/', [CalendarController::class, 'index'])->name('calendar.index');

    // Events
    Route::post('/events', [CalendarController::class, 'store'])->name('calendar.events.store');
    Route::get('/events/{event}', [CalendarController::class, 'show'])->name('calendar.show');
    Route::get('/events/{event}/edit', [CalendarController::class, 'edit'])->name('calendar.edit');
    Route::put('/events/{event}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/events/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::post('/events/quick-create', [CalendarController::class, 'quickCreate'])->name('calendar.quick-create');
    Route::post('/events/{event}/respond', [CalendarController::class, 'respondToInvite'])->name('calendar.respond');

    // Calendars
    Route::post('/calendars', [CalendarController::class, 'createCalendar'])->name('calendar.calendars.store');
    Route::delete('/calendars/{calendar}', [CalendarController::class, 'deleteCalendar'])->name('calendar.calendars.destroy');

    // API (JSON for AJAX) — throttled
    Route::get('/api/events', [CalendarController::class, 'apiEvents'])
        ->middleware('throttle:60,1')
        ->name('calendar.api.events');

    Route::get('/api/nepali', [CalendarController::class, 'nepaliApi'])
        ->middleware('throttle:60,1')
        ->name('calendar.api.nepali');

    // Event create form
    Route::get('/events/create', [CalendarController::class, 'edit'])->name('calendar.event.create');
});
