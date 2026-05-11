<?php

namespace App\Providers;

use App\Services\AppRegistryService;
use App\Services\AppStatusService;
use App\Services\EcosystemCommandService;
use App\Services\EnvEditorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AppRegistryService::class);
        $this->app->singleton(AppStatusService::class);
        $this->app->singleton(EcosystemCommandService::class);
        $this->app->singleton(EnvEditorService::class);
    }

    public function boot(): void
    {
        //
    }
}
