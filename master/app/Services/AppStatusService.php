<?php

namespace App\Services;

class AppStatusService
{
    public function __construct(private readonly AppRegistryService $registry) {}

    /**
     * Return a complete status snapshot for a single app.
     *
     * @return array{
     *   id: string,
     *   name: string,
     *   icon: string,
     *   path: string,
     *   url: string,
     *   exists: bool,
     *   health: string,
     *   http_code: int,
     *   commit: string,
     *   branch: string,
     *   dirty: bool,
     *   env: string,
     *   debug: bool,
     *   pending_migrations: int,
     *   last_updated: string|null,
     *   log_errors: int,
     * }
     */
    public function status(string $id): array
    {
        $app  = $this->registry->get($id) ?? [];
        $path = $app['path'] ?? '';

        $base = [
            'id'                 => $id,
            'name'               => $app['name'] ?? $id,
            'icon'               => $app['icon'] ?? '📦',
            'path'               => $path,
            'url'                => $app['url'] ?? '',
            'exists'             => is_dir($path),
            'health'             => 'unknown',
            'http_code'          => 0,
            'commit'             => '—',
            'branch'             => '—',
            'dirty'              => false,
            'env'                => '—',
            'debug'              => false,
            'pending_migrations' => 0,
            'last_updated'       => null,
            'log_errors'         => 0,
        ];

        if (! $base['exists']) {
            return $base;
        }

        return array_merge($base, [
            'health'             => $this->checkHealth($app),
            'http_code'          => $this->getHttpCode($app),
            'commit'             => $this->gitCommit($path),
            'branch'             => $this->gitBranch($path),
            'dirty'              => $this->gitIsDirty($path),
            'env'                => $this->readEnv($path, 'APP_ENV', '—'),
            'debug'              => $this->readEnv($path, 'APP_DEBUG', 'false') === 'true',
            'pending_migrations' => $this->countPendingMigrations($id, $path, $app),
            'last_updated'       => $this->lastUpdated($path),
            'log_errors'         => $this->recentLogErrors($path, $app),
            'deep_diagnostics'   => $this->getDeepDiagnostics($id, $path, $app),
        ]);

    }

    /** Status for all apps */
    public function all(): array
    {
        return collect($this->registry->all())
            ->mapWithKeys(fn ($_, $id) => [$id => $this->status($id)])
            ->toArray();
    }

    /** Return the last N lines of the Laravel log for an app */
    public function tailLog(string $id, int $lines = 100): string
    {
        $path    = $this->registry->path($id);
        $logFile = $path . '/storage/logs/laravel.log';

        if (! file_exists($logFile)) {
            return '(no log file found)';
        }

        $content = file_get_contents($logFile);
        $all     = explode("\n", $content);
        return implode("\n", array_slice($all, -$lines));
    }

    /** Parse pending migrations from artisan migrate:status */
    public function pendingMigrations(string $id): array
    {
        $path = $this->registry->path($id);
        if (! $this->registry->isLaravel($id) || ! is_file($path . '/artisan')) {
            return [];
        }

        $php    = config('ecosystem.php', 'php');
        $output = [];
        @exec("{$php} {$path}/artisan migrate:status --no-ansi 2>&1", $output);

        return array_values(array_filter($output, fn ($l) => str_contains($l, 'Pending')));
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function checkHealth(array $app): string
    {
        $code = $this->getHttpCode($app);

        return match (true) {
            $code >= 200 && $code < 300 => 'up',
            $code >= 500                => 'error',
            $code === 0                 => 'unreachable',
            default                     => 'degraded',
        };
    }

    private function getHttpCode(array $app): int
    {
        $url     = ($app['url'] ?? '') . ($app['health'] ?? '/');
        $timeout = config('ecosystem.health_timeout', 8);

        if (empty($app['url'])) {
            return 0;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_NOBODY         => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code;
    }

    private function gitCommit(string $path): string
    {
        $out = [];
        @exec("git -C " . escapeshellarg($path) . " rev-parse --short HEAD 2>/dev/null", $out);
        return $out[0] ?? '—';
    }

    private function gitBranch(string $path): string
    {
        $out = [];
        @exec("git -C " . escapeshellarg($path) . " rev-parse --abbrev-ref HEAD 2>/dev/null", $out);
        return $out[0] ?? '—';
    }

    private function gitIsDirty(string $path): bool
    {
        $out = [];
        @exec("git -C " . escapeshellarg($path) . " status --porcelain 2>/dev/null", $out);
        return count($out) > 0;
    }

    private function readEnv(string $path, string $key, string $default = ''): string
    {
        $envFile = $path . '/.env';
        if (! file_exists($envFile)) {
            return $default;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            if (trim($k) === $key) {
                return trim($v, '"\'');
            }
        }

        return $default;
    }

    private function countPendingMigrations(string $id, string $path, array $app): int
    {
        if (($app['type'] ?? 'laravel') !== 'laravel' || ! is_file($path . '/artisan')) {
            return 0;
        }

        $php    = config('ecosystem.php', 'php');
        $out    = [];
        @exec("{$php} {$path}/artisan migrate:status --no-ansi 2>&1", $out);

        return count(array_filter($out, fn ($l) => str_contains($l, 'Pending')));
    }

    private function lastUpdated(string $path): ?string
    {
        $stateFile = $path . '/.update-state/last-good-commit';
        if (! file_exists($stateFile)) {
            return null;
        }

        return date('Y-m-d H:i', filemtime($stateFile));
    }

    private function recentLogErrors(string $path, array $app): int
    {
        if (($app['type'] ?? 'laravel') !== 'laravel') {
            return 0;
        }

        $logFile = $path . '/storage/logs/laravel.log';
        if (! file_exists($logFile)) {
            return 0;
        }

        $content = file_get_contents($logFile);
        return substr_count($content, '.ERROR:');
    }

    private function getDeepDiagnostics(string $id, string $path, array $app): array
    {
        $diagnostics = [];

        // 1. Check if database is reachable
        $dbPath = $path . '/database/database.sqlite';
        if (file_exists($dbPath)) {
            $diagnostics['database'] = [
                'status' => 'ok',
                'size'   => round(filesize($dbPath) / 1024 / 1024, 2) . ' MB',
            ];
        }

        // 2. Service-specific checks
        if (str_contains($id, 'mail')) {
            $diagnostics['imap'] = $this->checkPort($this->readEnv($path, 'MAIL_HOST', 'localhost'), 993);
            $diagnostics['smtp'] = $this->checkPort($this->readEnv($path, 'MAIL_HOST', 'localhost'), 465);
        }

        if (str_contains($id, 'drive')) {
            $diagnostics['storage'] = [
                'used' => $this->getDirectorySize($path . '/storage/app'),
            ];
        }

        if (str_contains($id, 'ai')) {
            $diagnostics['model'] = [
                'status' => file_exists($path . '/storage/app/ai/model.bin') ? 'loaded' : 'missing',
            ];
        }

        return $diagnostics;
    }

    private function checkPort(string $host, int $port): string
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 2);
        if (is_resource($connection)) {
            fclose($connection);
            return 'open';
        }
        return 'closed';
    }

    private function getDirectorySize(string $path): string
    {
        if (!is_dir($path)) return '0 B';
        
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            $size += $file->getSize();
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}

