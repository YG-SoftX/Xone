<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__ . '/../routes/web.php',
            __DIR__ . '/../routes/admin.php',
        ],
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\DeviceFingerprinting::class, // Device tracking for authenticated users
            'service.status:account', // Dynamic maintenance/enabled check
        ]);

        $middleware->api(append: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\DeviceFingerprinting::class, // API device tracking
        ]);

        $middleware->alias([
            'admin.auth'   => \App\Http\Middleware\AdminAuth::class,
            'api.key'      => \App\Http\Middleware\ApiKeyAuth::class,
            'dev.api'      => \App\Http\Middleware\DeveloperApiAuth::class,
            'api.quota'    => \App\Http\Middleware\QuotaEnforcementMiddleware::class, // Enhanced quota enforcement
            'plan.feature' => \App\Http\Middleware\CheckPlanFeature::class,
            'service.status' => \App\Http\Middleware\CheckServiceStatus::class, // Service enable/disable check
            'otp.verified' => \App\Http\Middleware\EnsureOtpVerified::class,
            'isolated.payment.security' => \App\Http\Middleware\IsolatedPaymentSecurity::class, // Maximum security for isolated payment module
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
