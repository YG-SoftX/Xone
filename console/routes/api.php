<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\OAuthApplicationController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Project Management
    Route::apiResource('projects', ProjectController::class);
    Route::post('projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('projects/{project}/restore', [ProjectController::class, 'restore']);

    // API Key Management
    Route::get('projects/{project}/api-keys', [ApiKeyController::class, 'index']);
    Route::post('projects/{project}/api-keys', [ApiKeyController::class, 'store']);
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);
    Route::post('api-keys/{apiKey}/rotate', [ApiKeyController::class, 'rotate']);
    Route::get('api-keys/{apiKey}/remaining', [ApiKeyController::class, 'remaining']);

    // OAuth Application Management
    Route::get('projects/{project}/oauth-apps', [OAuthApplicationController::class, 'index']);
    Route::post('projects/{project}/oauth-apps', [OAuthApplicationController::class, 'store']);
    Route::put('oauth-apps/{app}', [OAuthApplicationController::class, 'update']);
    Route::delete('oauth-apps/{app}', [OAuthApplicationController::class, 'destroy']);
    Route::post('oauth-apps/{app}/regenerate-secret', [OAuthApplicationController::class, 'regenerateSecret']);

    // Billing & Subscriptions
    Route::get('projects/{project}/invoices', [BillingController::class, 'invoices']);
    Route::get('invoices/{invoice}', [BillingController::class, 'invoice']);
    Route::get('projects/{project}/subscriptions', [BillingController::class, 'subscriptions']);
    Route::post('projects/{project}/subscriptions', [BillingController::class, 'createSubscription']);
    Route::post('subscriptions/{subscription}/cancel', [BillingController::class, 'cancelSubscription']);
    Route::post('projects/{project}/generate-invoice', [BillingController::class, 'generateInvoice']);
    Route::get('projects/{project}/billing-stats', [BillingController::class, 'stats']);
});

// Public webhook endpoints (no authentication required - verified by signature)
Route::post('webhooks/yg-pay', [WebhookController::class, 'ygPayWebhook'])->name('webhooks.yg-pay');

// YG Master integration endpoints (signature verified)
Route::post('master/commands', [\App\Http\Controllers\Api\YgMasterController::class, 'handleCommand'])->name('master.commands');
Route::get('health', [\App\Http\Controllers\Api\YgMasterController::class, 'healthCheck'])->name('health.check');
