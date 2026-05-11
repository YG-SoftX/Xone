<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuotaController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────────
Route::get('/login', fn () => view('auth.login'))
    ->name('login')
    ->middleware('guest');

Route::get('/auth/sso/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::post('/logout', [SsoController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Projects ──────────────────────────────────────────────────────────────
    Route::get('/projects',                 [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create',          [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects',                [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{id}',            [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{id}/edit',       [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{id}',            [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{id}',         [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{id}/activate',  [ProjectController::class, 'activate'])->name('projects.activate');

    // ── Credentials (scoped under project) ────────────────────────────────────
    Route::get('/projects/{projectId}/credentials',        [CredentialController::class, 'index'])->name('projects.credentials');
    Route::get('/projects/{projectId}/credentials/create', [CredentialController::class, 'create'])->name('projects.credentials.create');
    Route::post('/projects/{projectId}/credentials',       [CredentialController::class, 'store'])->name('projects.credentials.store');
    Route::post('/credentials/{id}/rotate',  [CredentialController::class, 'rotate'])->name('credentials.rotate');
    Route::post('/credentials/{id}/toggle',  [CredentialController::class, 'toggle'])->name('credentials.toggle');
    Route::delete('/credentials/{id}',       [CredentialController::class, 'destroy'])->name('credentials.destroy');

    // ── Analytics ─────────────────────────────────────────────────────────────
    Route::get('/projects/{projectId}/analytics',        [AnalyticsController::class, 'index'])->name('projects.analytics');
    Route::get('/projects/{projectId}/analytics/export', [AnalyticsController::class, 'export'])->name('projects.analytics.export');

    // ── Quotas ────────────────────────────────────────────────────────────────
    Route::get('/projects/{projectId}/quotas',                      [QuotaController::class, 'index'])->name('projects.quotas');
    Route::put('/projects/{projectId}/quotas/{productId}',          [QuotaController::class, 'update'])->name('projects.quotas.update');

    // ── Webhooks (scoped under project) ───────────────────────────────────────
    Route::get('/projects/{projectId}/webhooks',        [WebhookController::class, 'index'])->name('projects.webhooks');
    Route::get('/projects/{projectId}/webhooks/create', [WebhookController::class, 'create'])->name('projects.webhooks.create');
    Route::post('/projects/{projectId}/webhooks',       [WebhookController::class, 'store'])->name('projects.webhooks.store');
    Route::get('/webhooks/{id}',                        [WebhookController::class, 'show'])->name('webhooks.show');
    Route::post('/webhooks/{id}/toggle',                [WebhookController::class, 'toggle'])->name('webhooks.toggle');
    Route::delete('/webhooks/{id}',                     [WebhookController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/webhooks/deliveries/{deliveryId}/redeliver', [WebhookController::class, 'redeliver'])->name('webhooks.redeliver');

    // ── Team ──────────────────────────────────────────────────────────────────
    Route::get('/projects/{projectId}/team',                   [TeamController::class, 'index'])->name('projects.team');
    Route::post('/projects/{projectId}/team/invite',           [TeamController::class, 'invite'])->name('projects.team.invite');
    Route::put('/projects/{projectId}/team/{memberId}/role',   [TeamController::class, 'updateRole'])->name('projects.team.role');
    Route::delete('/projects/{projectId}/team/{memberId}',     [TeamController::class, 'remove'])->name('projects.team.remove');

    // ── Billing ───────────────────────────────────────────────────────────────
    Route::get('/billing',                              [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/invoices',                     [BillingController::class, 'invoices'])->name('billing.invoices');
    Route::put('/billing/account',                      [BillingController::class, 'updateAccount'])->name('billing.update');
    Route::post('/billing/invoices/{id}/pay',           [BillingController::class, 'payInvoice'])->name('billing.pay');
    Route::post('/billing/payment-methods',             [BillingController::class, 'addPaymentMethod'])->name('billing.payment.add');
    Route::delete('/billing/payment-methods/{id}',      [BillingController::class, 'removePaymentMethod'])->name('billing.payment.remove');

    // ── API Tokens ────────────────────────────────────────────────────────────
    Route::get('/tokens',         [TokenController::class, 'index'])->name('tokens.index');
    Route::post('/tokens',        [TokenController::class, 'store'])->name('tokens.store');
    Route::delete('/tokens/{id}', [TokenController::class, 'destroy'])->name('tokens.destroy');

    // ── OAuth Apps ────────────────────────────────────────────────────────────
    Route::get('/apps',              [AppController::class, 'index'])->name('apps.index');
    Route::get('/apps/create',       [AppController::class, 'create'])->name('apps.create');
    Route::post('/apps',             [AppController::class, 'store'])->name('apps.store');
    Route::get('/apps/{id}',         [AppController::class, 'show'])->name('apps.show');
    Route::put('/apps/{id}',         [AppController::class, 'update'])->name('apps.update');
    Route::post('/apps/{id}/rotate', [AppController::class, 'rotateSecret'])->name('apps.rotate');
    Route::delete('/apps/{id}',      [AppController::class, 'destroy'])->name('apps.destroy');
});
