<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        try {
            // Safety guard for migrations: check if tables exist before sharing
            if (!app()->runningInConsole() || \Illuminate\Support\Facades\Schema::hasTable('themes')) {
                view()->share('activeTheme', \App\Helpers\ThemeHelper::getActiveTheme('account'));
            }

            if (!app()->runningInConsole() || \Illuminate\Support\Facades\Schema::hasTable('service_configurations')) {
                view()->share('enabledServices', \App\Models\ServiceConfiguration::where('is_enabled', true)->orderBy('id')->get());
            }
        } catch (\Exception $e) {
            // Database is offline; skip sharing database-driven views
        }
    }
}
