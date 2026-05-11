<?php

use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CredentialController;
use App\Http\Controllers\Api\DeveloperDashboardController;
use App\Http\Controllers\Api\DeveloperPortalController;
use App\Http\Controllers\Api\DeviceFingerprintController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\QuotaController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\GlobalSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/global-search', [GlobalSearchController::class, 'aggregate']);

// =============================================================================
// PUBLIC ENDPOINTS
// =============================================================================

// Device Fingerprint submission (from client-side JS) - rate limited
Route::post('/device/fingerprint', [DeviceFingerprintController::class, 'submit'])
    ->name('api.device.fingerprint')
    ->middleware('throttle:30,1');

// SSO validation — tight limit; callers should cache tokens client-side.
Route::get('/sso/validate', [SsoController::class, 'validateToken'])
    ->name('api.sso.validate')
    ->middleware('throttle:30,1');

// Payment Gateway Initiation (Ecosystem Apps)
Route::post('/payment/initiate', [\App\Http\Controllers\Api\PaymentGatewayController::class, 'initiate'])
    ->name('api.payment.initiate')
    ->middleware('throttle:20,1');

// Isolated Payment Module (Maximum Security)
Route::prefix('payment/isolated')->group(function () {
    Route::post('/process', [\App\Http\Controllers\Api\IsolatedPaymentController::class, 'processPayment'])
        ->name('api.payment.isolated.process')
        ->middleware('throttle:30,1');
    
    Route::get('/verify/{transactionId}', [\App\Http\Controllers\Api\IsolatedPaymentController::class, 'verifyPayment'])
        ->name('api.payment.isolated.verify')
        ->middleware('throttle:60,1');
    
    Route::post('/webhook', [\App\Http\Controllers\Api\IsolatedPaymentController::class, 'handleWebhook'])
        ->name('api.payment.isolated.webhook')
        ->middleware('throttle:120,1');
    
    // Admin-only routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/security-dashboard', [\App\Http\Controllers\Api\IsolatedPaymentController::class, 'getSecurityDashboard'])
            ->name('api.payment.isolated.dashboard');
        
        Route::post('/security-scan', [\App\Http\Controllers\Api\IsolatedPaymentController::class, 'runSecurityScan'])
            ->name('api.payment.isolated.scan');
        
        Route::post('/emergency-disable', [\App\Http\Controllers\Api\IsolatedPaymentController::class, 'emergencyDisable'])
            ->name('api.payment.isolated.emergency-disable');
        
        Route::post('/emergency-enable', [\App\Http\Controllers\Api\IsolatedPaymentController::class, 'reEnableAfterEmergency'])
            ->name('api.payment.isolated.emergency-enable');
    });
});

// Payment Webhooks — verified by provider signature, not by auth.
// Limit prevents log-flooding from bad actors.
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/payment/webhooks/stripe', [PaymentWebhookController::class, 'stripe'])
        ->name('api.payment.webhooks.stripe');

    Route::post('/payment/webhooks/paypal', [PaymentWebhookController::class, 'paypal'])
        ->name('api.payment.webhooks.paypal');

    Route::post('/payment/webhooks/razorpay', [PaymentWebhookController::class, 'razorpay'])
        ->name('api.payment.webhooks.razorpay');
});

// PayPal return URLs
Route::get('/payment/return/paypal', function () {
    return response()->json(['message' => 'PayPal payment completed. Please check your billing dashboard.']);
})->name('api.payment.return.paypal');

Route::get('/payment/cancel/paypal', function () {
    return response()->json(['message' => 'PayPal payment cancelled.'], 400);
})->name('api.payment.cancel.paypal');

// =============================================================================
// PROTECTED ENDPOINTS (Requires Sanctum Auth)
// =============================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ── Legacy API Token Management (60 req/min) ──
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/tokens', [ApiTokenController::class, 'index'])->name('api.tokens.index');
        Route::post('/tokens', [ApiTokenController::class, 'store'])->name('api.tokens.store');
        Route::delete('/tokens/{id}', [ApiTokenController::class, 'destroy'])->name('api.tokens.destroy');
        Route::post('/tokens/service-key', [ApiTokenController::class, 'generateServiceKey'])->name('api.tokens.service-key');
    });

    // ── Legacy Developer Portal — backwards compat (60 req/min) ──
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/developer/apps', [DeveloperPortalController::class, 'index'])->name('api.developer.apps');
        Route::post('/developer/apps', [DeveloperPortalController::class, 'store'])->name('api.developer.apps.store');
        Route::get('/developer/apps/{id}', [DeveloperPortalController::class, 'show'])->name('api.developer.apps.show');
        Route::put('/developer/apps/{id}', [DeveloperPortalController::class, 'update'])->name('api.developer.apps.update');
        Route::delete('/developer/apps/{id}', [DeveloperPortalController::class, 'destroy'])->name('api.developer.apps.destroy');
        Route::post('/developer/apps/{id}/rotate-secret', [DeveloperPortalController::class, 'rotateSecret'])->name('api.developer.apps.rotate-secret');
        Route::get('/developer/apps/{id}/stats', [DeveloperPortalController::class, 'stats'])->name('api.developer.apps.stats');
    });

    // ── Developer Platform (Google Cloud Console-style) ──

    // Dashboard (60 req/min — read-only overview)
    Route::get('/developer-console/dashboard', [DeveloperDashboardController::class, 'index'])
        ->name('api.dev-console.dashboard')
        ->middleware('throttle:60,1');

    // Projects (30 req/min — write operations are sensitive)
    Route::middleware('throttle:30,1')->prefix('developer-console/projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('api.dev-console.projects.index');
        Route::post('/', [ProjectController::class, 'store'])->name('api.dev-console.projects.store');
        Route::get('/{id}', [ProjectController::class, 'show'])->name('api.dev-console.projects.show');
        Route::put('/{id}', [ProjectController::class, 'update'])->name('api.dev-console.projects.update');
        Route::delete('/{id}', [ProjectController::class, 'destroy'])->name('api.dev-console.projects.destroy');
        Route::post('/{id}/activate', [ProjectController::class, 'activate'])->name('api.dev-console.projects.activate');
    });

    // Credentials — tighter limit; rotation and creation are high-value actions (20 req/min)
    Route::middleware('throttle:20,1')->group(function () {
        Route::prefix('developer-console/projects/{projectId}/credentials')->group(function () {
            Route::get('/', [CredentialController::class, 'index'])->name('api.dev-console.credentials.index');
            Route::post('/', [CredentialController::class, 'store'])->name('api.dev-console.credentials.store');
        });
        Route::prefix('developer-console/credentials')->group(function () {
            Route::get('/{id}', [CredentialController::class, 'show'])->name('api.dev-console.credentials.show');
            Route::put('/{id}', [CredentialController::class, 'update'])->name('api.dev-console.credentials.update');
            Route::delete('/{id}', [CredentialController::class, 'destroy'])->name('api.dev-console.credentials.destroy');
            Route::post('/{id}/rotate', [CredentialController::class, 'rotate'])->name('api.dev-console.credentials.rotate');
            Route::post('/{id}/toggle', [CredentialController::class, 'toggle'])->name('api.dev-console.credentials.toggle');
        });
    });

    // Analytics (60 req/min — read-only, but can be data-intensive)
    Route::middleware('throttle:60,1')->prefix('developer-console/projects/{projectId}/analytics')->group(function () {
        Route::get('/', [AnalyticsController::class, 'overview'])->name('api.dev-console.analytics.overview');
        Route::get('/product/{productId}', [AnalyticsController::class, 'byProduct'])->name('api.dev-console.analytics.by-product');
        Route::get('/endpoints', [AnalyticsController::class, 'byEndpoint'])->name('api.dev-console.analytics.by-endpoint');
        Route::get('/errors', [AnalyticsController::class, 'errors'])->name('api.dev-console.analytics.errors');
        Route::get('/latency', [AnalyticsController::class, 'latency'])->name('api.dev-console.analytics.latency');
        Route::get('/export', [AnalyticsController::class, 'export'])->name('api.dev-console.analytics.export');
        Route::get('/top-credentials', [AnalyticsController::class, 'topCredentials'])->name('api.dev-console.analytics.top-credentials');
    });

    // Quotas (30 req/min)
    Route::middleware('throttle:30,1')->prefix('developer-console/projects/{projectId}/quotas')->group(function () {
        Route::get('/', [QuotaController::class, 'index'])->name('api.dev-console.quotas.index');
        Route::get('/{productId}', [QuotaController::class, 'show'])->name('api.dev-console.quotas.show');
        Route::put('/{productId}', [QuotaController::class, 'update'])->name('api.dev-console.quotas.update');
        Route::get('/usage', [QuotaController::class, 'usage'])->name('api.dev-console.quotas.usage');
        Route::get('/alerts', [QuotaController::class, 'alerts'])->name('api.dev-console.quotas.alerts');
    });

    // Webhooks (30 req/min)
    Route::middleware('throttle:30,1')->group(function () {
        Route::prefix('developer-console/projects/{projectId}/webhooks')->group(function () {
            Route::get('/', [WebhookController::class, 'index'])->name('api.dev-console.webhooks.index');
            Route::post('/', [WebhookController::class, 'store'])->name('api.dev-console.webhooks.store');
        });
        Route::prefix('developer-console/webhooks')->group(function () {
            Route::get('/{id}', [WebhookController::class, 'show'])->name('api.dev-console.webhooks.show');
            Route::put('/{id}', [WebhookController::class, 'update'])->name('api.dev-console.webhooks.update');
            Route::post('/{id}/toggle', [WebhookController::class, 'toggle'])->name('api.dev-console.webhooks.toggle');
            Route::delete('/{id}', [WebhookController::class, 'destroy'])->name('api.dev-console.webhooks.destroy');
            Route::get('/{id}/deliveries', [WebhookController::class, 'deliveries'])->name('api.dev-console.webhooks.deliveries');
            Route::post('/deliveries/{deliveryId}/redeliver', [WebhookController::class, 'redeliver'])->name('api.dev-console.webhooks.redeliver');
            Route::post('/{id}/test', [WebhookController::class, 'test'])->name('api.dev-console.webhooks.test'); // Test webhook delivery
        });
    });

    // Team management (30 req/min)
    Route::middleware('throttle:30,1')->prefix('developer-console/projects/{projectId}/team')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('api.dev-console.team.index');
        Route::post('/', [TeamController::class, 'invite'])->name('api.dev-console.team.invite');
        Route::put('/{memberId}', [TeamController::class, 'updateRole'])->name('api.dev-console.team.update-role');
        Route::delete('/{memberId}', [TeamController::class, 'remove'])->name('api.dev-console.team.remove');
    });

    // Billing — payment actions get the tightest cap to prevent abuse (10 req/min for mutations)
    Route::prefix('developer-console/billing')->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/', [BillingController::class, 'index'])->name('api.dev-console.billing.index');
            Route::get('/account', [BillingController::class, 'account'])->name('api.dev-console.billing.account');
            Route::get('/invoices', [BillingController::class, 'invoices'])->name('api.dev-console.billing.invoices');
            Route::get('/invoices/{id}', [BillingController::class, 'invoice'])->name('api.dev-console.billing.invoice');
            Route::get('/usage', [BillingController::class, 'usage'])->name('api.dev-console.billing.usage');
            Route::get('/transactions', [BillingController::class, 'transactions'])->name('api.dev-console.billing.transactions');
            Route::get('/payment-config', [BillingController::class, 'paymentConfig'])->name('api.dev-console.billing.config');
        });

        // Mutating billing actions — very tight limit
        Route::middleware('throttle:10,1')->group(function () {
            Route::put('/account', [BillingController::class, 'updateAccount'])->name('api.dev-console.billing.update-account');
            Route::post('/invoices/{id}/pay', [BillingController::class, 'payInvoice'])->name('api.dev-console.billing.pay-invoice');
            Route::post('/payment-methods', [BillingController::class, 'addPaymentMethod'])->name('api.dev-console.billing.add-payment');
            Route::delete('/payment-methods/{methodId}', [BillingController::class, 'removePaymentMethod'])->name('api.dev-console.billing.remove-payment');
            Route::post('/transactions/{id}/refund', [BillingController::class, 'refundTransaction'])->name('api.dev-console.billing.refund');
        });
    });

    // ── Ecosystem Event Bus (120 req/min) ──
    Route::middleware('throttle:120,1')->prefix('events')->group(function () {
        Route::post('/publish', [\App\Http\Controllers\Api\EventController::class, 'publish'])->name('api.events.publish');
    });

    // ── Unified Storage API (60 req/min) ──
    Route::middleware('throttle:60,1')->prefix('storage')->group(function () {
        Route::post('/upload', [\App\Http\Controllers\Api\UnifiedStorageController::class, 'upload'])->name('api.storage.upload');
        Route::get('/stats', [\App\Http\Controllers\Api\UnifiedStorageController::class, 'stats'])->name('api.storage.stats');
    });

    // Webhook management — admin only (20 req/min)
    Route::middleware('throttle:20,1')->prefix('admin/payment-webhooks')->group(function () {
        Route::get('/status', [PaymentWebhookController::class, 'status'])->name('api.admin.webhooks.status');
        Route::post('/{id}/retry', [PaymentWebhookController::class, 'retry'])->name('api.admin.webhooks.retry');
    });
});
