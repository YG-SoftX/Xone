<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\HomeThemeService::class);
        $this->app->singleton(\App\Services\EcosystemService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share theme service with views that extend layouts.app
        // so CSS variables, logo, and copyright are always available.
        View::composer(['layouts.app', 'search.*', 'admin.*', 'ecosystem.*'], function (\Illuminate\View\View $view) {
            $view->with('themeService', app(\App\Services\HomeThemeService::class));
        });
    }
}
