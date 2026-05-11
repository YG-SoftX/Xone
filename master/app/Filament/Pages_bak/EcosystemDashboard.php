<?php

namespace App\Filament\Pages;

use App\Models\AppModule;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AppRegistryService;
use App\Services\AppStatusService;
use App\Services\EcosystemCommandService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class EcosystemDashboard extends Page
{
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Ecosystem Live';
    protected static \BackedEnum|string|null $navigationGroup = 'Infrastructure';
    protected static ?string $title           = 'YG Master Control Panel';
    protected static ?int    $navigationSort  = 1;
    protected string $view = 'filament.pages.ecosystem-dashboard';

    // Dashboard Data
    public array   $statuses       = [];
    public array   $ecosystemStats = [];
    public array   $serviceHealth  = [];
    public array   $recentAlerts   = [];
    public string  $commandOutput  = '';
    public string  $commandTitle   = '';
    public bool    $showOutput     = false;
    public bool    $execAvailable  = true;

    public function mount(): void
    {
        $this->execAvailable = function_exists('exec');
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $this->statuses = app(AppStatusService::class)->all();
        $this->loadEcosystemStats();
        $this->loadServiceHealth();
        $this->loadRecentAlerts();
    }

    private function loadEcosystemStats(): void
    {
        try {
            $this->ecosystemStats = [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'total_tenants' => Tenant::count(),
                'active_subscriptions' => Subscription::where('status', 'active')->count(),
                'monthly_revenue' => Subscription::where('status', 'active')
                    ->whereMonth('created_at', now()->month)
                    ->sum('amount'),
                'yg_pay_status' => 'operational',
                'active_gateways' => 9, // Stripe, PayPal, Razorpay, ConnectIPS, Fonepay, UPI, TrueLayer, Khalti, YG Pay
                'total_apps' => AppModule::count(),
                'active_apps' => AppModule::where('is_active', true)->count(),
                'total_audit_logs' => AuditLog::count(),
                'critical_alerts' => AuditLog::where('severity', 'critical')
                    ->whereDate('created_at', today())
                    ->count(),
            ];
        } catch (\Exception $e) {
            $this->ecosystemStats = ['error' => 'Database connection issue'];
        }
    }

    private function loadServiceHealth(): void
    {
        $registry = app(AppRegistryService::class);
        $apps = $registry->all();
        
        foreach ($apps as $appId => $appConfig) {
            $health = $this->checkServiceHealth($appConfig);
            $this->serviceHealth[$appId] = $health;
        }
    }

    private function checkServiceHealth(array $appConfig): array
    {
        try {
            $url = rtrim($appConfig['url'], '/') . '/api/health';
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'response_time' => $response->headers()['Transfer-Encoding'] ?? 'N/A',
                    'last_check' => now()->toIso8601String(),
                    'version' => $response->json('version', 'unknown'),
                ];
            }
            
            return [
                'status' => 'unhealthy',
                'error' => 'HTTP ' . $response->status(),
                'last_check' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unreachable',
                'error' => $e->getMessage(),
                'last_check' => now()->toIso8601String(),
            ];
        }
    }

    private function loadRecentAlerts(): void
    {
        try {
            $this->recentAlerts = AuditLog::orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($log) => [
                    'id' => $log->id,
                    'message' => $log->description,
                    'severity' => $log->severity ?? 'info',
                    'timestamp' => $log->created_at->diffForHumans(),
                    'source' => $log->source ?? 'system',
                ])
                ->toArray();
        } catch (\Exception $e) {
            $this->recentAlerts = [];
        }
    }

    // ── Service Management Actions ─────────────────────────────────────────────

    public function restartService(string $id): void
    {
        $result = app(EcosystemCommandService::class)->restartQueue($id);
        $this->showResult("Restart Service: {$id}", $result);
        $this->loadDashboardData();
    }

    public function clearCache(string $id): void
    {
        $result = app(EcosystemCommandService::class)->clearCache($id);
        $this->showResult("Clear Cache: {$id}", $result);
        $this->loadDashboardData();
    }

    public function updateApp(string $id): void
    {
        $result = app(EcosystemCommandService::class)->update($id);
        $this->showResult("Update: {$id}", $result);
        $this->loadDashboardData();
    }

    public function rollbackApp(string $id): void
    {
        $result = app(EcosystemCommandService::class)->rollback($id);
        $this->showResult("Rollback: {$id}", $result);
        $this->loadDashboardData();
    }

    public function migrateApp(string $id): void
    {
        $result = app(EcosystemCommandService::class)->migrate($id);
        $this->showResult("Migrations: {$id}", $result);
        $this->loadDashboardData();
    }

    public function backupApp(string $id): void
    {
        $result = app(EcosystemCommandService::class)->backup($id);
        $this->showResult("Backup: {$id}", $result);
    }

    public function pullApp(string $id): void
    {
        $result = app(EcosystemCommandService::class)->gitPull($id);
        $this->showResult("Git Pull: {$id}", $result);
        $this->loadDashboardData();
    }

    public function rebuildCaches(string $id): void
    {
        $result = app(EcosystemCommandService::class)->rebuildCaches($id);
        $this->showResult("Rebuild Caches: {$id}", $result);
        $this->loadDashboardData();
    }

    public function maintenanceDown(string $id): void
    {
        $result = app(EcosystemCommandService::class)->toggleMaintenance($id, true);
        $this->showResult("Maintenance On: {$id}", $result);
        $this->loadDashboardData();
    }

    public function maintenanceUp(string $id): void
    {
        $result = app(EcosystemCommandService::class)->toggleMaintenance($id, false);
        $this->showResult("Maintenance Off: {$id}", $result);
        $this->loadDashboardData();
    }

    public function updateAll(): void
    {
        $registry = app(AppRegistryService::class);
        $cmd      = app(EcosystemCommandService::class);
        $outputs  = [];

        foreach (array_keys($registry->all()) as $id) {
            $result    = $cmd->update($id);
            $outputs[] = "=== {$id} ===\n" . $result['output'];
        }

        $this->showResult('Update All Apps', [
            'success' => true,
            'output'  => implode("\n\n", $outputs),
        ]);

        $this->loadDashboardData();
    }

    public function healthCheckAll(): void
    {
        $this->loadDashboardData();

        $down = collect($this->statuses)
            ->filter(fn ($s) => $s['health'] !== 'up')
            ->pluck('name')
            ->join(', ');

        if ($down) {
            Notification::make()->warning()
                ->title('Some services are not healthy')
                ->body("Unhealthy: {$down}")
                ->send();
        } else {
            Notification::make()->success()
                ->title('All services are healthy')
                ->send();
        }
    }

    public function closeOutput(): void
    {
        $this->showOutput    = false;
        $this->commandOutput = '';
    }

    // ── Header actions ─────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshStatus')
                ->label('Refresh Dashboard')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->loadDashboardData()),

            Action::make('healthCheckAll')
                ->label('Health Check All Services')
                ->icon('heroicon-o-heart')
                ->color('success')
                ->action(fn () => $this->healthCheckAll()),

            Action::make('updateAll')
                ->label('Update All Services')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Update All Ecosystem Services')
                ->modalDescription('This will update all services in dependency order. Each service will be put into maintenance mode, updated, migrated, and caches rebuilt. Continue?')
                ->action(fn () => $this->updateAll()),

            Action::make('emergencyMode')
                ->label('Emergency Mode')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Activate Emergency Mode')
                ->modalDescription('This will enable maintenance mode on ALL services immediately. Only use in emergency situations.')
                ->action(function () {
                    $registry = app(AppRegistryService::class);
                    foreach (array_keys($registry->all()) as $id) {
                        $this->maintenanceDown($id);
                    }
                    Notification::make()
                        ->danger()
                        ->title('Emergency Mode Activated')
                        ->body('All services are now in maintenance mode')
                        ->send();
                }),
        ];
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function showResult(string $title, array $result): void
    {
        $this->commandTitle  = $title;
        $this->commandOutput = $result['output'];
        $this->showOutput    = true;

        if ($result['success']) {
            Notification::make()->success()->title($title . ' — complete')->send();
        } else {
            Notification::make()->danger()->title($title . ' — failed')->body('See output below.')->send();
        }
    }
}
