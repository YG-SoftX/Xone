<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\SsoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Admin routes (must be before wildcard routes)
require __DIR__ . '/admin.php';

// SSO routes
Route::get('/sso/initiate', function (\Illuminate\Http\Request $request) {
    // Redirect to YG Account SSO initiation
    $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
    $callback = url('/sso/callback');
    $service = 'YG Mail';

    // If this request came from a third-party app, pass along the client_id
    $clientId = $request->query('client_id');
    $queryParams = http_build_query([
        'service' => $service,
        'callback' => $callback,
    ]);
    if ($clientId) {
        $queryParams .= '&client_id=' . urlencode($clientId);
    }

    return redirect($accountUrl . '/sso/initiate?' . $queryParams);
})->name('sso.initiate');

Route::get('/sso/callback', [\App\Http\Controllers\SsoController::class, 'callback'])->name('sso.callback');

Route::get('/', [\App\Http\Controllers\LandingPageController::class, 'index'])->name('welcome');

// Protected API routes — all require authentication
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/mail/inbox', [MailController::class, 'getInbox']);
    Route::post('/mail/send', [MailController::class, 'send'])->middleware('throttle:30,1');
    Route::patch('/mail/{id}/read', [MailController::class, 'markRead'])->middleware('throttle:60,1');
    Route::delete('/mail/{id}', [MailController::class, 'delete'])->middleware('throttle:30,1');
    Route::get('/mail/search', [MailController::class, 'search'])->middleware('throttle:30,1');
    Route::get('/mail/attachment/{id}/download', [MailController::class, 'downloadAttachment'])->name('mail.attachment.download');
    Route::post('/backup', [MailController::class, 'backup'])->middleware('throttle:10,1');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
