<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Settings\SecuritySettingsController;
use App\Http\Controllers\Settings\PrivacySettingsController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\IntegrationSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PollingController;
use App\Http\Controllers\SearchController;

// Root route - Intelligent redirection
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('root');

// SSO routes (public)
Route::get('/sso/initiate', [\App\Http\Controllers\SsoController::class, 'initiate'])->name('sso.initiate');
Route::get('/sso/logout', [\App\Http\Controllers\SsoController::class, 'logout'])->name('sso.logout');
Route::post('/sso/logout', [\App\Http\Controllers\SsoController::class, 'logout'])->name('sso.logout.post');

require __DIR__ . '/auth.php';

// ==========================================
// UNIFIED DASHBOARD (Google-style Home)
// ==========================================
Route::middleware(['auth'])->group(function () {
    
    // Main Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/activity', [\App\Http\Controllers\ProfileController::class, 'activity'])->name('profile.activity');
    Route::get('/activity/analytics', [\App\Http\Controllers\ProfileController::class, 'analytics'])->name('profile.analytics');
    
    // Quick Actions
    Route::get('/compose', [DashboardController::class, 'composeEmail'])->name('compose.email');
    
    // ==========================================
    // YG SOCIETY ROUTES
    // ==========================================
    Route::prefix('society')->name('society.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SocietyController::class, 'index'])->name('index');
        Route::post('/posts', [\App\Http\Controllers\SocietyController::class, 'store'])->name('store');
        Route::post('/posts/{post}/like', [\App\Http\Controllers\SocietyController::class, 'toggleLike'])->name('like');
        Route::post('/posts/{post}/comment', [\App\Http\Controllers\SocietyController::class, 'addComment'])->name('comment');
        Route::post('/transfer', [\App\Http\Controllers\SocietyController::class, 'transferStones'])->name('transfer');
        Route::delete('/posts/{post}', [\App\Http\Controllers\SocietyController::class, 'destroy'])->name('destroy');
    });

    Route::get('/upload', [DashboardController::class, 'uploadFile'])->name('upload.file');
    
    // Activity Feed API
    Route::get('/api/activity-feed', [DashboardController::class, 'getActivityFeed'])->name('activity.feed');
    
    // ==========================================
    // REAL-TIME SYNC (AJAX Polling for cPanel)
    // ==========================================
    Route::post('/api/polling/check-updates', [PollingController::class, 'checkUpdates'])->name('polling.updates');
    Route::post('/api/push/register', [PollingController::class, 'registerPushToken'])->name('push.register');
    Route::post('/api/push/unregister', [PollingController::class, 'unregisterPushToken'])->name('push.unregister');
    
    // ==========================================
    // GLOBAL SEARCH (Legacy)
    // ==========================================
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/api/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
    
    // ==========================================
    // UNIFIED SEARCH (New - Cross-Service)
    // ==========================================
    Route::prefix('api/unified-search')->name('unified-search.')->group(function () {
        Route::post('/search', [\App\Http\Controllers\UnifiedSearchController::class, 'search'])->name('search');
        Route::get('/suggestions', [\App\Http\Controllers\UnifiedSearchController::class, 'suggestions'])->name('suggestions');
        Route::post('/index', [\App\Http\Controllers\UnifiedSearchController::class, 'indexContent'])->name('index');
        Route::delete('/remove', [\App\Http\Controllers\UnifiedSearchController::class, 'removeContent'])->name('remove');
        Route::post('/rebuild', [\App\Http\Controllers\UnifiedSearchController::class, 'rebuildIndex'])->name('rebuild')->middleware('admin.auth');
    });
    
    // ==========================================
    // UNIFIED NOTIFICATION CENTER
    // ==========================================
    Route::prefix('api/notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/summary', [\App\Http\Controllers\NotificationController::class, 'summary'])->name('summary');
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
        Route::post('/', [\App\Http\Controllers\NotificationController::class, 'store'])->name('store')->middleware('admin.auth'); // Internal — admin/service use only
    });
    
    // ==========================================
    // YG MAIL ROUTES
    // ==========================================
    Route::prefix('mail')->name('mail.')->middleware('service.status:mail')->group(function () {
        Route::get('/', [\App\Modules\Mail\Controllers\MailController::class, 'index'])->name('index');
        Route::get('/compose', [\App\Modules\Mail\Controllers\MailController::class, 'compose'])->name('compose');
        Route::post('/send', [\App\Modules\Mail\Controllers\MailController::class, 'send'])->name('send');
        Route::get('/{messageId}', [\App\Modules\Mail\Controllers\MailController::class, 'show'])->name('show');
        Route::delete('/{messageId}', [\App\Modules\Mail\Controllers\MailController::class, 'destroy'])->name('destroy');
        Route::get('/attachment/{attachmentId}/download', [\App\Modules\Mail\Controllers\MailController::class, 'downloadAttachment'])->name('attachment.download');
    });
    
    // ==========================================
    // YG DRIVE ROUTES
    // ==========================================
    Route::prefix('drive')->name('drive.')->middleware('service.status:drive')->group(function () {
        Route::get('/', [\App\Modules\Drive\Controllers\DriveController::class, 'index'])->name('index');
        Route::post('/upload', [\App\Modules\Drive\Controllers\DriveController::class, 'upload'])->name('upload');
        Route::post('/folder', [\App\Modules\Drive\Controllers\DriveController::class, 'createFolder'])->name('folder.create');
        Route::get('/file/{fileId}/download', [\App\Modules\Drive\Controllers\DriveController::class, 'download'])->name('file.download');
        Route::post('/file/{fileId}/share', [\App\Modules\Drive\Controllers\DriveController::class, 'share'])->name('file.share');
        Route::delete('/file/{fileId}', [\App\Modules\Drive\Controllers\DriveController::class, 'destroy'])->name('file.destroy');
    });
    
    // ==========================================
    // YG DOCS ROUTES
    // ==========================================
    Route::prefix('docs')->name('docs.')->middleware('service.status:docs')->group(function () {
        Route::get('/', [\App\Modules\Docs\Controllers\DocsController::class, 'index'])->name('index');
        Route::post('/create', [\App\Modules\Docs\Controllers\DocsController::class, 'create'])->name('create');
        Route::get('/{documentId}/edit', [\App\Modules\Docs\Controllers\DocsController::class, 'edit'])->name('edit');
        Route::put('/{documentId}', [\App\Modules\Docs\Controllers\DocsController::class, 'update'])->name('update');
        Route::post('/{documentId}/comment', [\App\Modules\Docs\Controllers\DocsController::class, 'addComment'])->name('comment.add');
        Route::post('/{documentId}/share', [\App\Modules\Docs\Controllers\DocsController::class, 'share'])->name('share');
        Route::delete('/{documentId}', [\App\Modules\Docs\Controllers\DocsController::class, 'destroy'])->name('destroy');
    });
    
    // ==========================================
    // YG MEET ROUTES
    // ==========================================
    Route::prefix('meet')->name('meet.')->middleware('service.status:meet')->group(function () {
        Route::get('/', [\App\Modules\Meet\Controllers\MeetController::class, 'index'])->name('index');
        Route::post('/schedule', [\App\Modules\Meet\Controllers\MeetController::class, 'schedule'])->name('schedule');
        Route::get('/{meetingCode}', [\App\Modules\Meet\Controllers\MeetController::class, 'show'])->name('show');
        Route::post('/{meetingCode}/join', [\App\Modules\Meet\Controllers\MeetController::class, 'join'])->name('join');
        Route::post('/{meetingCode}/chat', [\App\Modules\Meet\Controllers\MeetController::class, 'sendChatMessage'])->name('chat.send');
        Route::post('/{meetingCode}/leave', [\App\Modules\Meet\Controllers\MeetController::class, 'leave'])->name('leave');
    });
    
    // ==========================================
    // YG PAY ROUTES
    // ==========================================
    Route::prefix('pay')->name('pay.')->middleware(['service.status:pay', 'otp.verified'])->group(function () {
        Route::get('/', [\App\Modules\Pay\Controllers\PayController::class, 'index'])->name('index');
        Route::post('/send', [\App\Modules\Pay\Controllers\PayController::class, 'sendMoney'])->name('send');
        Route::post('/deposit', [\App\Modules\Pay\Controllers\PayController::class, 'deposit'])->name('deposit');
        Route::post('/withdraw', [\App\Modules\Pay\Controllers\PayController::class, 'withdraw'])->name('withdraw');
        Route::get('/transaction/{transactionId}', [\App\Modules\Pay\Controllers\PayController::class, 'showTransaction'])->name('transaction.show');
    });
    
    // ==========================================
    // YG AI ROUTES
    // ==========================================
    Route::prefix('ai')->name('ai.')->middleware('service.status:ai')->group(function () {
        Route::get('/', [\App\Modules\AI\Controllers\AiController::class, 'index'])->name('index');
        Route::post('/query', [\App\Modules\AI\Controllers\AiController::class, 'query'])->name('query');
        Route::post('/smart-reply', [\App\Modules\AI\Controllers\AiController::class, 'getSmartReplies'])->name('smart-reply');
        Route::post('/categorize-email', [\App\Modules\AI\Controllers\AiController::class, 'categorizeEmail'])->name('categorize-email');
        Route::post('/summarize-document', [\App\Modules\AI\Controllers\AiController::class, 'summarizeDocument'])->name('summarize-document');
    });
    
    // ==========================================
    // YG FORMS ROUTES
    // ==========================================
    Route::prefix('forms')->name('forms.')->middleware('service.status:forms')->group(function () {
        Route::get('/', [\App\Modules\Forms\Controllers\FormsController::class, 'index'])->name('index');
        Route::get('/create', [\App\Modules\Forms\Controllers\FormsController::class, 'create'])->name('create');
        Route::post('/', [\App\Modules\Forms\Controllers\FormsController::class, 'store'])->name('store');
        Route::get('/{formId}/edit', [\App\Modules\Forms\Controllers\FormsController::class, 'edit'])->name('edit');
        Route::put('/{formId}', [\App\Modules\Forms\Controllers\FormsController::class, 'update'])->name('update');
        Route::post('/{formId}/questions', [\App\Modules\Forms\Controllers\FormsController::class, 'addQuestion'])->name('questions.add');
        Route::put('/{formId}/questions/{questionId}', [\App\Modules\Forms\Controllers\FormsController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/{formId}/questions/{questionId}', [\App\Modules\Forms\Controllers\FormsController::class, 'deleteQuestion'])->name('questions.delete');
        Route::get('/{formId}/responses', [\App\Modules\Forms\Controllers\FormsController::class, 'responses'])->name('responses');
        Route::post('/{formId}/share', [\App\Modules\Forms\Controllers\FormsController::class, 'share'])->name('share');
        Route::delete('/{formId}', [\App\Modules\Forms\Controllers\FormsController::class, 'destroy'])->name('destroy');
    });
    
    // ==========================================
    // YG XCEL ROUTES (Spreadsheet Service)
    // ==========================================
    Route::prefix('xcel')->name('xcel.')->middleware('service.status:xcel')->group(function () {
        Route::get('/', [\App\Modules\Xcel\Controllers\XcelController::class, 'index'])->name('index');
        Route::get('/create', [\App\Modules\Xcel\Controllers\XcelController::class, 'create'])->name('create');
        Route::post('/', [\App\Modules\Xcel\Controllers\XcelController::class, 'store'])->name('store');
        Route::get('/{workbookId}/edit', [\App\Modules\Xcel\Controllers\XcelController::class, 'edit'])->name('edit');
        Route::put('/{workbookId}', [\App\Modules\Xcel\Controllers\XcelController::class, 'update'])->name('update');
        Route::post('/{workbookId}/sheets', [\App\Modules\Xcel\Controllers\XcelController::class, 'addSheet'])->name('sheets.add');
        Route::put('/{workbookId}/sheets/{sheetId}/cells/{cellId}', [\App\Modules\Xcel\Controllers\XcelController::class, 'updateCell'])->name('cells.update');
        Route::post('/{workbookId}/sheets/{sheetId}/cells/batch', [\App\Modules\Xcel\Controllers\XcelController::class, 'batchUpdateCells'])->name('cells.batch');
        Route::get('/{workbookId}/sheets/{sheetId}/cells/range', [\App\Modules\Xcel\Controllers\XcelController::class, 'getCellRange'])->name('cells.range');
        Route::post('/{workbookId}/sheets/{sheetId}/charts', [\App\Modules\Xcel\Controllers\XcelController::class, 'addChart'])->name('charts.add');
        Route::post('/{workbookId}/share', [\App\Modules\Xcel\Controllers\XcelController::class, 'share'])->name('share');
        Route::get('/{workbookId}/export/{format?}', [\App\Modules\Xcel\Controllers\XcelController::class, 'export'])->name('export');
        Route::delete('/{workbookId}', [\App\Modules\Xcel\Controllers\XcelController::class, 'destroy'])->name('destroy');
    });
});

// Public form routes (no authentication required)
Route::prefix('f')->group(function () {
    Route::get('/{uuid}', [\App\Modules\Forms\Controllers\FormsController::class, 'show'])->name('forms.public.show');
    Route::post('/{uuid}/submit', [\App\Modules\Forms\Controllers\FormsController::class, 'submit'])->name('forms.public.submit')->middleware('throttle:10,1');
});

// Developer Console Routes (Web UI)
Route::middleware(['auth'])->prefix('developer')->name('developer.')->group(function () {
    
    // Dashboard
    Route::get('/', [App\Http\Controllers\Api\DeveloperDashboardController::class, 'view'])->name('dashboard');
    
    // Projects
    Route::get('/projects', [App\Http\Controllers\Api\ProjectController::class, 'viewIndex'])->name('projects.index');
    Route::get('/projects/{id}', [App\Http\Controllers\Api\ProjectController::class, 'viewShow'])->name('projects.show');
    
    // Credentials
    Route::get('/projects/{projectId}/credentials', [App\Http\Controllers\Api\CredentialController::class, 'viewIndex'])->name('credentials.index');
    
    // Analytics
    Route::get('/projects/{projectId}/analytics', [App\Http\Controllers\Api\AnalyticsController::class, 'viewIndex'])->name('analytics.index');
    
    // Quotas
    Route::get('/projects/{projectId}/quotas', [App\Http\Controllers\Api\QuotaController::class, 'viewIndex'])->name('quotas.index');
    
    // Webhooks
    Route::get('/projects/{projectId}/webhooks', [App\Http\Controllers\Api\WebhookController::class, 'viewIndex'])->name('webhooks.index');
    Route::get('/projects/{projectId}/webhooks/{webhookId}', [App\Http\Controllers\Api\WebhookController::class, 'viewShow'])->name('webhooks.show');
    
    // Team
    Route::get('/projects/{projectId}/team', [App\Http\Controllers\Api\TeamController::class, 'viewIndex'])->name('team.index');
    
    // Billing
    Route::get('/billing', [App\Http\Controllers\Api\BillingController::class, 'viewIndex'])->name('billing.index');
    Route::get('/billing/invoices', [App\Http\Controllers\Api\BillingController::class, 'viewInvoices'])->name('billing.invoices');
});

// Settings Routes Group
// ── Billing & Subscription ─────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('settings/billing')->name('billing.')->group(function () {
    Route::get('/upgrade', [\App\Http\Controllers\SubscriptionController::class, 'upgrade'])->name('upgrade');
    Route::post('/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::get('/confirm', [\App\Http\Controllers\SubscriptionController::class, 'confirm'])->name('confirm');
    Route::post('/storage-pack', [\App\Http\Controllers\SubscriptionController::class, 'addStoragePack'])->name('storage-pack');
    Route::post('/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
});

// ==========================================
// SUPPORT KNOWLEDGE BASE (support.ygxone.com)
// ==========================================
// Named support.sub.* to avoid collision with the path-prefix fallback routes below
Route::domain('support.' . parse_url(config('app.url'), PHP_URL_HOST))->group(function () {
    Route::get('/', [\App\Http\Controllers\SupportController::class, 'index'])->name('support.sub.index');
    Route::get('/search', [\App\Http\Controllers\SupportController::class, 'search'])->name('support.sub.search');
    Route::get('/category/{category}', [\App\Http\Controllers\SupportController::class, 'category'])->name('support.sub.category');
    Route::get('/article/{slug}', [\App\Http\Controllers\SupportController::class, 'show'])->name('support.sub.article');
});

// Fallback support routes (for main domain)
Route::prefix('support')->name('support.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SupportController::class, 'index'])->name('index');
    Route::get('/search', [\App\Http\Controllers\SupportController::class, 'search'])->name('search');
    Route::get('/category/{category}', [\App\Http\Controllers\SupportController::class, 'category'])->name('category');
    Route::get('/article/{slug}', [\App\Http\Controllers\SupportController::class, 'show'])->name('show');
});

// ==========================================
// YG VAULT (passwordmanager.ygxone.com)
// ==========================================
Route::domain('passwordmanager.' . parse_url(config('app.url'), PHP_URL_HOST))->name('vault.')->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [\App\Http\Controllers\PasswordManagerController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\PasswordManagerController::class, 'store'])->name('store');
        Route::put('/{password}', [\App\Http\Controllers\PasswordManagerController::class, 'update'])->name('update');
        Route::delete('/{password}', [\App\Http\Controllers\PasswordManagerController::class, 'destroy'])->name('destroy');
    });
});

// ==========================================
// ORGANIZATION HUB (Google Admin Style)
// ==========================================
Route::middleware(['auth'])->prefix('organization')->name('organization.')->group(function () {
    Route::get('/', [\App\Http\Controllers\OrganizationController::class, 'index'])->name('index');
    Route::post('/invite', [\App\Http\Controllers\OrganizationController::class, 'invite'])->name('invite');
    Route::post('/members/{member}/toggle-status', [\App\Http\Controllers\OrganizationController::class, 'toggleMemberStatus'])->name('members.toggle-status');
    Route::delete('/members/{member}', [\App\Http\Controllers\OrganizationController::class, 'removeMember'])->name('members.remove');
    
    // Domain Management
    Route::get('/domains', [\App\Http\Controllers\OrganizationController::class, 'domains'])->name('domains');
    Route::post('/domains', [\App\Http\Controllers\OrganizationController::class, 'addDomain'])->name('domains.add');
    Route::post('/domains/{domain}/verify', [\App\Http\Controllers\OrganizationController::class, 'verifyDomain'])->name('domains.verify');
    
    Route::get('/settings', [\App\Http\Controllers\OrganizationController::class, 'settings'])->name('settings');
});

// ==========================================
// UNIFIED BILLING HUB (Google Style)
// ==========================================
Route::middleware(['auth'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/', [\App\Http\Controllers\BillingController::class, 'index'])->name('index');
    Route::get('/invoices', [\App\Http\Controllers\BillingController::class, 'invoices'])->name('invoices');
    Route::get('/usage', [\App\Http\Controllers\BillingController::class, 'usage'])->name('usage');
    Route::post('/payment-methods', [\App\Http\Controllers\BillingController::class, 'updatePaymentMethod'])->name('payment-methods.update');
});

Route::middleware(['auth'])->prefix('vault')->name('vault.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PasswordManagerController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\PasswordManagerController::class, 'store'])->name('store');
    Route::put('/{password}', [\App\Http\Controllers\PasswordManagerController::class, 'update'])->name('update');
    Route::delete('/{password}', [\App\Http\Controllers\PasswordManagerController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {

    // Security Settings
    Route::get('/security', [SecuritySettingsController::class, 'index'])->name('security.index');
    Route::post('/security/update-password', [SecuritySettingsController::class, 'updatePassword'])->middleware('otp.verified')->name('security.update-password');
    Route::post('/security/enable-2fa', [SecuritySettingsController::class, 'enableTwoFactor'])->name('security.enable-2fa');
    Route::post('/security/verify-2fa', [SecuritySettingsController::class, 'verifyTwoFactor'])->name('security.verify-2fa');
    Route::post('/security/disable-2fa', [SecuritySettingsController::class, 'disableTwoFactor'])->middleware('otp.verified')->name('security.disable-2fa');
    Route::post('/security/terminate-sessions', [SecuritySettingsController::class, 'terminateSessions'])->name('security.terminate-sessions');
    
    // Privacy Settings
    Route::get('/privacy', [PrivacySettingsController::class, 'index'])->name('privacy.index');
    Route::post('/privacy/update-preferences', [PrivacySettingsController::class, 'updatePreferences'])->name('privacy.update-preferences');
    Route::post('/privacy/request-export', [PrivacySettingsController::class, 'requestDataExport'])->name('privacy.request-export');
    Route::get('/privacy/download/{exportId}', [PrivacySettingsController::class, 'downloadDataExport'])->name('privacy.download-export');
    Route::post('/privacy/request-deletion', [PrivacySettingsController::class, 'requestAccountDeletion'])->name('privacy.request-deletion');
    Route::post('/privacy/cancel-deletion', [PrivacySettingsController::class, 'cancelAccountDeletion'])->name('privacy.cancel-deletion');
    Route::post('/privacy/update-cookie-consent', [PrivacySettingsController::class, 'updateCookieConsent'])->name('privacy.update-cookie-consent');
    
    // Notification Settings
    Route::get('/notifications', [NotificationSettingsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/update', [NotificationSettingsController::class, 'updatePreferences'])->name('notifications.update');
    Route::post('/notifications/test', [NotificationSettingsController::class, 'testNotification'])->name('notifications.test');
    Route::post('/notifications/email-settings', [NotificationSettingsController::class, 'updateEmailSettings'])->name('notifications.email-settings');
    Route::post('/notifications/subscribe-newsletter', [NotificationSettingsController::class, 'subscribeNewsletter'])->name('notifications.subscribe-newsletter');
    Route::post('/notifications/unsubscribe-newsletter', [NotificationSettingsController::class, 'unsubscribeNewsletter'])->name('notifications.unsubscribe-newsletter');
    
    // Integration Settings (API Keys & Webhooks Only - No OAuth)
    Route::get('/integrations', [IntegrationSettingsController::class, 'index'])->name('integrations.index');
    Route::post('/integrations/tokens', [IntegrationSettingsController::class, 'generateApiToken'])->name('integrations.tokens.create');
    Route::delete('/integrations/tokens/{tokenId}', [IntegrationSettingsController::class, 'revokeApiToken'])->name('integrations.tokens.revoke');
    Route::post('/integrations/webhooks', [IntegrationSettingsController::class, 'storeWebhook'])->name('integrations.webhooks.store');
    Route::post('/integrations/webhooks/{webhookId}/toggle', [IntegrationSettingsController::class, 'toggleWebhook'])->name('integrations.webhooks.toggle');
    Route::delete('/integrations/webhooks/{webhookId}', [IntegrationSettingsController::class, 'deleteWebhook'])->name('integrations.webhooks.delete');
    Route::post('/integrations/webhooks/{webhookId}/test', [IntegrationSettingsController::class, 'testWebhook'])->name('integrations.webhooks.test');
});

// ==========================================
// YG ADS HUB (ads.ygxone.com)
// ==========================================
Route::domain('ads.' . parse_url(config('app.url'), PHP_URL_HOST))->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\OrganizationController::class, 'adsIndex'])->name('ads.index');
    Route::get('/campaigns', [\App\Http\Controllers\OrganizationController::class, 'adsCampaigns'])->name('ads.campaigns');
    Route::get('/billing', [\App\Http\Controllers\OrganizationController::class, 'adsBilling'])->name('ads.billing');
});

// ==========================================
// YG ADSENSE HUB (adsense.ygxone.com)
// ==========================================
Route::domain('adsense.' . parse_url(config('app.url'), PHP_URL_HOST))->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\OrganizationController::class, 'adsenseIndex'])->name('adsense.index');
    Route::get('/sites', [\App\Http\Controllers\OrganizationController::class, 'adsenseSites'])->name('adsense.sites');
    Route::get('/earnings', [\App\Http\Controllers\OrganizationController::class, 'adsenseEarnings'])->name('adsense.earnings');
});
