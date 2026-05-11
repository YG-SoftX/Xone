<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EcosystemCommandService
{
    public function __construct(private readonly AppRegistryService $registry) {}

    /**
     * Run the app's smart update script.
     * Returns ['success' => bool, 'output' => string]
     */
    public function update(string $id): array
    {
        $path = $this->registry->path($id);
        $lib  = config('ecosystem.lib');

        if (! is_dir($path)) {
            return $this->fail("Path not found: {$path}");
        }

        $updateScript = $path . '/scripts/update.sh';

        if (file_exists($updateScript) && file_exists($lib)) {
            return $this->run("ECOSYSTEM_LIB=" . escapeshellarg($lib) .
                " bash " . escapeshellarg($updateScript), $id, 'update');
        }

        // Fallback: run artisan commands directly
        return $this->runArtisanSequence($id, $path, [
            'down --retry=30',
            'migrate --force',
            'optimize:clear',
            'config:cache',
            'route:cache',
            'view:cache',
            'up',
        ]);
    }

    /**
     * Rollback the app to its last saved git commit.
     */
    public function rollback(string $id): array
    {
        $path   = $this->registry->path($id);
        $lib    = config('ecosystem.lib');
        $script = dirname(dirname($lib)) . '/rollback.sh';

        if (file_exists($script) && file_exists($lib)) {
            return $this->run(
                "ECOSYSTEM_LIB=" . escapeshellarg($lib) .
                " bash " . escapeshellarg($script) .
                " --app " . escapeshellarg($id),
                $id,
                'rollback'
            );
        }

        // Fallback: git checkout last good commit
        $commitFile = $path . '/.update-state/last-good-commit';
        if (! file_exists($commitFile)) {
            return $this->fail('No rollback point found. Run an update first.');
        }

        $commit = trim(file_get_contents($commitFile));
        return $this->run(
            "git -C " . escapeshellarg($path) . " checkout " . escapeshellarg($commit) . " -- .",
            $id,
            'rollback'
        );
    }

    /**
     * Run database migrations for an app.
     */
    public function migrate(string $id): array
    {
        return $this->artisan($id, 'migrate --force');
    }

    /**
     * Run a backup for an app.
     */
    public function backup(string $id): array
    {
        $path   = $this->registry->path($id);
        $script = $path . '/scripts/backup.sh';

        if (file_exists($script)) {
            return $this->run("bash " . escapeshellarg($script), $id, 'backup');
        }

        return $this->fail('backup.sh not found for this app.');
    }

    /**
     * Allowed artisan commands that can be triggered from the ecosystem panel.
     * Anything not in this list is rejected before execution.
     */
    const ALLOWED_ARTISAN_COMMANDS = [
        'migrate', 'migrate:fresh', 'migrate:rollback', 'migrate:status',
        'down', 'up',
        'optimize', 'optimize:clear',
        'config:cache', 'config:clear',
        'route:cache', 'route:clear',
        'view:cache', 'view:clear',
        'event:cache', 'event:clear',
        'queue:restart',
        'storage:link',
        'schedule:run',
    ];

    /**
     * Run an artisan command for a Laravel app.
     * Only commands from ALLOWED_ARTISAN_COMMANDS may be executed.
     */
    public function artisan(string $id, string $command): array
    {
        $path = $this->registry->path($id);
        $php  = config('ecosystem.php', 'php');

        if (! $this->registry->isLaravel($id) || ! is_file($path . '/artisan')) {
            return $this->fail('Not a Laravel app or artisan not found.');
        }

        // Validate the base command name against the allowlist
        $parts   = preg_split('/\s+/', trim($command));
        $cmdName = $parts[0] ?? '';

        if (! in_array($cmdName, self::ALLOWED_ARTISAN_COMMANDS, true)) {
            return $this->fail("Artisan command '{$cmdName}' is not permitted.");
        }

        // Escape every argument individually so no part of $command can inject shell syntax
        $escaped = array_map('escapeshellarg', $parts);

        return $this->run(
            escapeshellarg($php)
            . ' ' . escapeshellarg($path . '/artisan')
            . ' ' . implode(' ', $escaped)
            . ' --no-ansi 2>&1',
            $id,
            "artisan:{$cmdName}"
        );
    }

    /**
     * Pull latest code from git origin.
     */
    public function gitPull(string $id): array
    {
        $path = $this->registry->path($id);
        return $this->run(
            "git -C " . escapeshellarg($path) . " pull origin " .
            "$(git -C " . escapeshellarg($path) . " rev-parse --abbrev-ref HEAD) 2>&1",
            $id,
            'git-pull'
        );
    }

    /**
     * Clear and rebuild all Laravel caches.
     */
    public function rebuildCaches(string $id): array
    {
        return $this->runArtisanSequence($id, $this->registry->path($id), [
            'optimize:clear',
            'config:cache',
            'route:cache',
            'view:cache',
            'event:cache',
        ]);
    }

    /**
     * Toggle maintenance mode on/off.
     */
    public function toggleMaintenance(string $id, bool $down): array
    {
        return $this->artisan($id, $down ? 'down --retry=30' : 'up');
    }

    /**
     * Restart queue workers for a service
     */
    public function restartQueue(string $id): array
    {
        return $this->artisan($id, 'queue:restart');
    }

    /**
     * Clear all caches for a service
     */
    public function clearCache(string $id): array
    {
        return $this->artisan($id, 'optimize:clear');
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function run(string $command, string $appId, string $action): array
    {
        if (! function_exists('exec')) {
            return $this->fail(
                'exec() is disabled on this server. Run commands manually via SSH.'
            );
        }

        $output     = [];
        $returnCode = 0;

        Log::info("Ecosystem command: {$action} on {$appId}", ['command' => $command]);

        @exec($command . ' 2>&1', $output, $returnCode);

        $outputStr = implode("\n", $output);

        if ($returnCode !== 0) {
            Log::error("Ecosystem command failed: {$action} on {$appId}", [
                'code'   => $returnCode,
                'output' => $outputStr,
            ]);
        }

        return [
            'success' => $returnCode === 0,
            'output'  => $outputStr ?: '(no output)',
            'code'    => $returnCode,
        ];
    }

    private function runArtisanSequence(string $id, string $path, array $commands): array
    {
        $php    = config('ecosystem.php', 'php');
        $output = [];

        foreach ($commands as $cmd) {
            $result = $this->run(
                "{$php} " . escapeshellarg($path . '/artisan') . " {$cmd} --no-ansi 2>&1",
                $id,
                "artisan:{$cmd}"
            );
            $output[] = "$ artisan {$cmd}";
            $output[] = $result['output'];

            if (! $result['success'] && ! str_contains($cmd, 'down')) {
                break;
            }
        }

        return [
            'success' => true,
            'output'  => implode("\n", $output),
        ];
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'output' => $message, 'code' => 1];
    }
}
