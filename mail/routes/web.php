<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\MailViewController;
use App\Http\Controllers\SsoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

// Admin routes (must be before wildcard routes)
require __DIR__ . '/admin.php';

// SSO routes
Route::get('/sso/initiate', function (\Illuminate\Http\Request $request) {
    $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
    $callback = url('/sso/callback');
    $service = 'YG Mail';
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

Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

Route::get('/', [\App\Http\Controllers\LandingPageController::class, 'index'])->name('welcome');

// ========================
// Mail Frontend (Blade Views)
// ========================
Route::middleware(['auth'])->prefix('mail')->name('mail.')->group(function () {
    Route::get('/', [MailViewController::class, 'inbox'])->name('inbox');
    Route::get('/poll', [MailViewController::class, 'poll'])->name('poll');
    Route::get('/search', [MailViewController::class, 'search'])->name('search');

    // Email actions
    Route::post('/send', [MailViewController::class, 'send'])->name('send');
    Route::post('/{id}/star', [MailViewController::class, 'toggleStar'])->name('star');
    Route::post('/{id}/delete', [MailViewController::class, 'delete'])->name('delete');
    Route::post('/{id}/move', [MailViewController::class, 'moveToFolder'])->name('move');
    Route::get('/{id}', [MailViewController::class, 'show'])->name('show');

    // Reply within email detail
    Route::post('/{id}/reply', [MailViewController::class, 'reply'])->name('reply');
});

// ========================
// Legacy API Routes (for backward compatibility)
// ========================
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/mail/inbox', [MailController::class, 'getInbox']);
    Route::post('/mail/send', [MailController::class, 'send'])->middleware('throttle:30,1');
    Route::patch('/mail/{id}/read', [MailController::class, 'markRead'])->middleware('throttle:60,1');
    Route::delete('/mail/{id}', [MailController::class, 'delete'])->middleware('throttle:30,1');
    Route::get('/mail/search', [MailController::class, 'search'])->middleware('throttle:30,1');
    Route::get('/mail/attachment/{id}/download', [MailController::class, 'downloadAttachment'])->name('mail.attachment.download');
    Route::post('/backup', [MailController::class, 'backup'])->middleware('throttle:10,1')->name('api.backup');
});

Route::get('/dashboard', function () {
    return redirect()->route('mail.inbox');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
