<?php

Route::get('/', function () {
    return redirect('/admin');
});

// API Routes for Service Management
Route::middleware(['auth'])->prefix('api')->group(function () {
    
    // Footer Management API (Public - no auth required for reading)
    Route::get('/footer/{serviceKey?}', [\App\Http\Controllers\Api\FooterController::class, 'index'])
        ->where('serviceKey', '[a-zA-Z0-9_-]+')
        ->name('api.footer.index');

    // Health Check Endpoints
    Route::prefix('health')->group(function () {
        Route::get('/status', [\App\Http\Controllers\HealthCheckController::class, 'getStatus'])->name('api.health.status');
        Route::post('/check-all', [\App\Http\Controllers\HealthCheckController::class, 'checkAll'])->name('api.health.check-all');
        Route::post('/check/{slug}', [\App\Http\Controllers\HealthCheckController::class, 'checkService'])->name('api.health.check-service');
    });

    // Electron App Endpoints
    Route::prefix('ecosystem')->group(function () {
        Route::post('/register', [\App\Http\Controllers\Api\ElectronAppController::class, 'register'])->name('api.ecosystem.register');
        Route::post('/heartbeat', [\App\Http\Controllers\Api\ElectronAppController::class, 'heartbeat'])->name('api.ecosystem.heartbeat');
        Route::get('/config/{appId}', [\App\Http\Controllers\Api\ElectronAppController::class, 'getConfig'])->name('api.ecosystem.config');
        Route::put('/config/{appId}', [\App\Http\Controllers\Api\ElectronAppController::class, 'updateConfig'])->name('api.ecosystem.update-config');
        Route::get('/status', [\App\Http\Controllers\Api\ElectronAppController::class, 'getStatus'])->name('api.ecosystem.status');
        Route::delete('/unregister/{appId}', [\App\Http\Controllers\Api\ElectronAppController::class, 'unregister'])->name('api.ecosystem.unregister');
    });

    // Deployment Endpoints
    Route::prefix('deployments')->group(function () {
        Route::post('/{id}/deploy', [\App\Http\Controllers\DeploymentController::class, 'deploy'])->name('api.deployments.deploy');
        Route::post('/bulk-deploy', [\App\Http\Controllers\DeploymentController::class, 'deployAll'])->name('api.deployments.bulk-deploy');
        Route::get('/{id}/status', [\App\Http\Controllers\DeploymentController::class, 'getStatus'])->name('api.deployments.status');
    });

    // Backup Endpoints
    Route::prefix('backups')->group(function () {
        Route::post('/create', [\App\Http\Controllers\BackupController::class, 'create'])->name('api.backups.create');
        Route::get('/list', [\App\Http\Controllers\BackupController::class, 'list'])->name('api.backups.list');
        Route::get('/download/{filename}', [\App\Http\Controllers\BackupController::class, 'download'])->name('api.backups.download');
        Route::delete('/{filename}', [\App\Http\Controllers\BackupController::class, 'delete'])->name('api.backups.delete');
    });

    // Ecosystem Status
    Route::get('/ecosystem/status', function () {
        $services = \App\Models\AppModule::select('name', 'slug', 'status', 'last_health_check')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
            'summary' => [
                'total' => $services->count(),
                'healthy' => $services->where('status', 'healthy')->count(),
                'degraded' => $services->where('status', 'degraded')->count(),
                'unhealthy' => $services->where('status', 'unhealthy')->count(),
            ],
        ]);
    })->name('api.ecosystem.status');

    // Maintenance Mode Toggle
    Route::post('/maintenance/toggle/{slug}', function ($slug) {
        $service = \App\Models\AppModule::where('slug', $slug)->firstOrFail();
        
        $service->update([
            'is_active' => !$service->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Service '{$service->name}' is now " . ($service->is_active ? 'active' : 'in maintenance'),
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'is_active' => $service->is_active,
            ],
        ]);
    })->name('api.maintenance.toggle');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/guardian/dashboard', [\App\Http\Controllers\GuardianController::class, 'index'])->name('guardian.dashboard');
    
    Route::prefix('admin/ai-training')->group(function () {
        Route::get('/', [\App\Http\Controllers\AiTrainingController::class, 'index'])->name('ai-training.index');
        Route::post('/clear', [\App\Http\Controllers\AiTrainingController::class, 'clear'])->name('ai-training.clear');
    });
});