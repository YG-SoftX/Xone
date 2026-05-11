<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminPlatformController;
use App\Http\Controllers\Admin\AdminFeatureController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\AdminSmtpController;
use App\Http\Controllers\Admin\AdminNfcController;
use App\Http\Controllers\Admin\AdminThirdPartyController;
use App\Http\Controllers\Admin\MasterDashboardController;
use App\Http\Controllers\Admin\MasterEmailController;
use App\Http\Controllers\Admin\MasterDocumentController;
use App\Http\Controllers\Admin\MasterServiceController;
use App\Http\Controllers\Admin\AdminThemeController;
use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminDocController;
use App\Http\Controllers\Admin\AdminArticleController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminDeviceController;
use App\Http\Controllers\Admin\AdminDeviceAnalyticsController;
use App\Http\Controllers\Admin\AdminSystemController;
use App\Http\Controllers\Admin\EnvironmentConfigController;
use Illuminate\Support\Facades\Route;

// Admin Login (public) — rate limited to 10 attempts/minute per IP
Route::get('/admin/login', [AdminDashboardController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminDashboardController::class, 'login'])->middleware('throttle:10,1');

// Admin Routes (authenticated) — baseline 120 req/min; sensitive routes get tighter caps below
Route::middleware(['admin.auth', 'throttle:120,1'])->prefix('admin')->name('admin.')->group(function () {
    // Master Dashboard
    Route::get('/', [MasterDashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AdminDashboardController::class, 'logout'])->name('logout');
    Route::post('/quick-action', [MasterDashboardController::class, 'quickAction'])->name('quick-action');

    // Environment Configuration
    Route::get('/environment', [EnvironmentConfigController::class, 'index'])->name('environment.index');
    Route::post('/environment/update', [EnvironmentConfigController::class, 'update'])->name('environment.update')->middleware('throttle:5,1');
    Route::post('/environment/test-database', [EnvironmentConfigController::class, 'testDatabaseConnection'])->name('environment.test-database')->middleware('throttle:3,1');
    Route::post('/environment/test-email', [EnvironmentConfigController::class, 'testEmailConfiguration'])->name('environment.test-email')->middleware('throttle:3,1');

    // Service Management
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'index'])->name('index');
        Route::post('/{serviceKey}/enable', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'enable'])->name('enable')->middleware('throttle:10,1');
        Route::post('/{serviceKey}/disable', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'disable'])->name('disable')->middleware('throttle:10,1');
        Route::post('/{serviceKey}/maintenance/enable', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'enableMaintenance'])->name('maintenance.enable')->middleware('throttle:10,1');
        Route::post('/{serviceKey}/maintenance/disable', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'disableMaintenance'])->name('maintenance.disable')->middleware('throttle:10,1');
        Route::put('/{serviceKey}/settings', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'updateSettings'])->name('settings.update')->middleware('throttle:20,1');
        Route::get('/{serviceKey}/logs', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'logs'])->name('logs');
        Route::get('/{serviceKey}/analytics', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'analytics'])->name('analytics');
        Route::post('/seed', [\App\Http\Controllers\Admin\ServiceManagementController::class, 'seedServices'])->name('seed')->middleware('throttle:2,1');
    });

    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{id}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
    Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])->name('users.role')->middleware('throttle:10,1');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy')->middleware('throttle:10,1');
    Route::post('/users/{id}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate')->middleware('throttle:5,1');

    // Platform Management
    Route::get('/platform', [AdminPlatformController::class, 'index'])->name('platform.index');
    Route::get('/platform/users', [AdminPlatformController::class, 'users'])->name('platform.users');
    Route::patch('/platform/users/{id}/status', [AdminPlatformController::class, 'updateUserStatus'])->name('platform.users.status');
    Route::get('/platform/subscriptions', [AdminPlatformController::class, 'subscriptions'])->name('platform.subscriptions');
    Route::patch('/platform/subscriptions/{id}/cancel', [AdminPlatformController::class, 'cancelSubscription'])->name('platform.subscriptions.cancel')->middleware('throttle:10,1');
    Route::get('/platform/wallets', [AdminPlatformController::class, 'wallets'])->name('platform.wallets');
    Route::post('/platform/wallets/{id}/adjust', [AdminPlatformController::class, 'adjustWalletBalance'])->name('platform.wallets.adjust')->middleware('throttle:10,1');

    // KYC Management
    Route::get('/kyc', [AdminUserController::class, 'kycReview'])->name('kyc.index');
    Route::post('/kyc/{id}/approve', [AdminUserController::class, 'kycApprove'])->name('kyc.approve')->middleware('throttle:20,1');
    Route::post('/kyc/{id}/reject', [AdminUserController::class, 'kycReject'])->name('kyc.reject')->middleware('throttle:20,1');

    // Services & Features (Master — health checks, toggles, feature flags)
    // Prefixed /platform/ to avoid URL + name collision with ServiceManagementController above
    Route::prefix('platform')->name('platform.')->group(function () {
        Route::get('/services', [MasterServiceController::class, 'index'])->name('services.index');
        Route::post('/services/check-all', [MasterServiceController::class, 'checkAllHealth'])->name('services.check-all');
        Route::post('/services/{id}/check', [MasterServiceController::class, 'checkHealth'])->name('services.check');
        Route::post('/services/{id}/toggle', [MasterServiceController::class, 'toggleService'])->name('services.toggle');
        Route::patch('/services/{id}/maintenance', [MasterServiceController::class, 'setMaintenance'])->name('services.maintenance');
        Route::get('/features', [MasterServiceController::class, 'features'])->name('features.index');
        Route::post('/features/{id}/toggle', [MasterServiceController::class, 'toggleFeature'])->name('features.toggle');
        Route::post('/features/{id}/visibility', [MasterServiceController::class, 'toggleFeatureVisibility'])->name('features.visibility');
    });

    // Email Management (YG Mail)
    Route::get('/emails', [MasterEmailController::class, 'index'])->name('emails.index');
    Route::get('/emails/list', [MasterEmailController::class, 'emails'])->name('emails.list');
    Route::get('/emails/{id}', [MasterEmailController::class, 'showEmail'])->name('emails.show');
    Route::delete('/emails/{id}', [MasterEmailController::class, 'deleteEmail'])->name('emails.delete');
    Route::get('/smtp', [MasterEmailController::class, 'smtpAccounts'])->name('smtp.index');
    Route::post('/smtp/{id}/toggle', [MasterEmailController::class, 'toggleSmtp'])->name('smtp.toggle');
    Route::post('/smtp/{id}/test', [MasterEmailController::class, 'testSmtp'])->name('smtp.test');
    Route::get('/mail/config', [MasterEmailController::class, 'config'])->name('mail.config');
    Route::post('/mail/imap/test', [MasterEmailController::class, 'testImap'])->name('mail.imap-test');
    Route::get('/mail/queue', [MasterEmailController::class, 'queue'])->name('mail.queue');
    Route::post('/mail/queue/retry', [MasterEmailController::class, 'retryQueue'])->name('mail.queue-retry');

    // Document Management (YG DocX)
    Route::get('/documents', [MasterDocumentController::class, 'documents'])->name('documents.index');
    Route::get('/documents/{id}', [MasterDocumentController::class, 'showDocument'])->name('documents.show');
    Route::delete('/documents/{id}', [MasterDocumentController::class, 'deleteDocument'])->name('documents.delete');
    Route::get('/documents/{id}/versions', [MasterDocumentController::class, 'documentVersions'])->name('documents.versions');

    // Spreadsheet Management (YG Xcel)
    Route::get('/spreadsheets', [MasterDocumentController::class, 'spreadsheets'])->name('spreadsheets.index');
    Route::get('/spreadsheets/{id}', [MasterDocumentController::class, 'showSpreadsheet'])->name('spreadsheets.show');
    Route::delete('/spreadsheets/{id}', [MasterDocumentController::class, 'deleteSpreadsheet'])->name('spreadsheets.delete');

    // Templates (Both Services)
    Route::get('/templates', [MasterDocumentController::class, 'templates'])->name('templates.index');

    // Invoice Management
    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{id}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('/invoices/{id}/status', [AdminInvoiceController::class, 'updateStatus'])->name('invoices.status');
    Route::post('/invoices/{id}/mark-paid', [AdminInvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{id}/resend', [AdminInvoiceController::class, 'resend'])->name('invoices.resend');
    Route::delete('/invoices/{id}', [AdminInvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('throttle:10,1');

    // NFC Management
    Route::get('/nfc/tokens', [AdminNfcController::class, 'tokens'])->name('nfc.tokens');
    Route::post('/nfc/tokens/{id}/toggle', [AdminNfcController::class, 'toggleToken'])->name('nfc.toggle');
    Route::get('/nfc/transactions', [AdminNfcController::class, 'transactions'])->name('nfc.transactions');
    Route::get('/nfc/transactions/{id}', [AdminNfcController::class, 'showTransaction'])->name('nfc.transaction.show');

    // ===== CMS: Theme & Customization =====
    Route::get('/theme', [AdminThemeController::class, 'index'])->name('theme.index');
    Route::post('/theme/{id?}', [AdminThemeController::class, 'update'])->name('theme.update');
    Route::post('/theme/{id}/reset', [AdminThemeController::class, 'reset'])->name('theme.reset');
    Route::get('/theme/{id}/preview', [AdminThemeController::class, 'preview'])->name('theme.preview');
    Route::delete('/theme/{id}', [AdminThemeController::class, 'destroy'])->name('theme.destroy');

    // ===== CMS: Frontend Content =====
    Route::get('/content', [AdminContentController::class, 'index'])->name('content.index');
    Route::get('/content/create', [AdminContentController::class, 'create'])->name('content.create');
    Route::post('/content', [AdminContentController::class, 'store'])->name('content.store');
    Route::get('/content/{id}/edit', [AdminContentController::class, 'edit'])->name('content.edit');
    Route::put('/content/{id}', [AdminContentController::class, 'update'])->name('content.update');
    Route::delete('/content/{id}', [AdminContentController::class, 'destroy'])->name('content.destroy');

    // ===== CMS: Documentation =====
    Route::get('/docs', [AdminDocController::class, 'index'])->name('docs.index');
    Route::get('/docs/create', [AdminDocController::class, 'create'])->name('docs.create');
    Route::post('/docs', [AdminDocController::class, 'store'])->name('docs.store');
    Route::get('/docs/{id}/edit', [AdminDocController::class, 'edit'])->name('docs.edit');
    Route::put('/docs/{id}', [AdminDocController::class, 'update'])->name('docs.update');
    Route::delete('/docs/{id}', [AdminDocController::class, 'destroy'])->name('docs.destroy');
    Route::post('/docs/{id}/reorder', [AdminDocController::class, 'reorder'])->name('docs.reorder');

    // ===== CMS: Articles =====
    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{id}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{id}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::post('/articles/{id}/publish', [AdminArticleController::class, 'publish'])->name('articles.publish');
    Route::post('/articles/{id}/unpublish', [AdminArticleController::class, 'unpublish'])->name('articles.unpublish');
    Route::delete('/articles/{id}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');

    // ===== CMS: Support Tickets =====
    Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
    Route::get('/support/{id}', [AdminSupportController::class, 'show'])->name('support.show');
    Route::post('/support/{id}/assign', [AdminSupportController::class, 'assign'])->name('support.assign');
    Route::post('/support/{id}/reply', [AdminSupportController::class, 'reply'])->name('support.reply');
    Route::post('/support/{id}/resolve', [AdminSupportController::class, 'resolve'])->name('support.resolve');
    Route::post('/support/{id}/close', [AdminSupportController::class, 'close'])->name('support.close');
    Route::post('/support/{id}/reopen', [AdminSupportController::class, 'reopen'])->name('support.reopen');
    Route::delete('/support/{id}', [AdminSupportController::class, 'destroy'])->name('support.destroy');
    Route::post('/support/bulk', [AdminSupportController::class, 'bulkAction'])->name('support.bulk')->middleware('throttle:10,1');

    // Third-Party App Management
    Route::get('/third-party-apps', [AdminThirdPartyController::class, 'index'])->name('third-party-apps.index');
    Route::get('/third-party-apps/{id}', [AdminThirdPartyController::class, 'show'])->name('third-party-apps.show');
    Route::post('/third-party-apps/{id}/toggle', [AdminThirdPartyController::class, 'toggleStatus'])->name('third-party-apps.toggle');
    Route::delete('/third-party-apps/{id}', [AdminThirdPartyController::class, 'destroy'])->name('third-party-apps.destroy')->middleware('throttle:10,1');
    Route::get('/third-party-logs', [AdminThirdPartyController::class, 'authLogs'])->name('third-party-apps.logs');

    // Device Management (per-user)
    Route::get('/users/{userId}/devices', [AdminDeviceController::class, 'index'])->name('devices.index');
    Route::post('/devices/{deviceId}/block', [AdminDeviceController::class, 'block'])->name('devices.block')->middleware('throttle:20,1');
    Route::post('/devices/{deviceId}/unblock', [AdminDeviceController::class, 'unblock'])->name('devices.unblock')->middleware('throttle:20,1');
    Route::delete('/devices/{deviceId}', [AdminDeviceController::class, 'destroy'])->name('devices.destroy')->middleware('throttle:10,1');
    Route::delete('/users/{userId}/devices/all', [AdminDeviceController::class, 'destroyAll'])->name('devices.destroy-all')->middleware('throttle:5,1');

    // Device Analytics & Fraud Detection
    Route::get('/device-analytics', [AdminDeviceAnalyticsController::class, 'index'])->name('device-analytics.index');
    Route::get('/device-analytics/fraud-alerts', [AdminDeviceAnalyticsController::class, 'fraudAlerts'])->name('device-analytics.fraud-alerts');
    Route::post('/device-analytics/alerts/{id}/review', [AdminDeviceAnalyticsController::class, 'reviewAlert'])->name('device-analytics.alerts.review')->middleware('throttle:30,1');
    Route::get('/device-analytics/suspicious-links', [AdminDeviceAnalyticsController::class, 'suspiciousLinks'])->name('device-analytics.suspicious-links');
    Route::get('/device-analytics/devices/{id}', [AdminDeviceAnalyticsController::class, 'showDevice'])->name('device-analytics.device-detail');
    Route::post('/device-analytics/devices/{id}/toggle-block', [AdminDeviceAnalyticsController::class, 'toggleBlock'])->name('device-analytics.toggle-block')->middleware('throttle:20,1');
    Route::post('/device-analytics/devices/{id}/toggle-trust', [AdminDeviceAnalyticsController::class, 'toggleTrust'])->name('device-analytics.toggle-trust')->middleware('throttle:20,1');
    Route::get('/device-analytics/api/activity-timeline', [AdminDeviceAnalyticsController::class, 'activityTimeline'])->name('device-analytics.activity-timeline');
    Route::get('/device-analytics/export/user/{id}', [AdminDeviceAnalyticsController::class, 'exportUserData'])->name('device-analytics.export')->middleware('throttle:10,1');

    // System Management
    Route::get('/system', [AdminSystemController::class, 'index'])->name('system.index');
    Route::post('/system/maintenance/enable', [AdminSystemController::class, 'maintenanceEnable'])->name('system.maintenance.enable')->middleware('throttle:5,1');
    Route::post('/system/maintenance/disable', [AdminSystemController::class, 'maintenanceDisable'])->name('system.maintenance.disable')->middleware('throttle:5,1');
    Route::post('/system/cache/clear', [AdminSystemController::class, 'cacheClear'])->name('system.cache.clear')->middleware('throttle:10,1');
    Route::post('/system/cache/warm', [AdminSystemController::class, 'cacheWarm'])->name('system.cache.warm')->middleware('throttle:10,1');
    Route::post('/system/queue/restart', [AdminSystemController::class, 'queueRestart'])->name('system.queue.restart')->middleware('throttle:10,1');
});
