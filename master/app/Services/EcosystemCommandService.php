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

        // Handle electron apps differently
        if ($this->registry->getType($id) === 'electron') {
            return $this->updateElectronApp($id);
        }

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
     * Update electron app (send update command to client)
     */
    private function updateElectronApp(string $id): array
    {
        // For electron apps, we can't update directly since they run on user machines
        // Instead, we update the configuration to prompt users to update
        $app = \App\Models\AppModule::where('slug', $id)->first();
        if ($app) {
            $app->update([
                'config' => array_merge(
                    json_decode($app->config ?? '{}', true) ?: [],
                    ['needs_update' => true, 'update_available' => true]
                )
            ]);
        }

        return [
            'success' => true,
            'output' => "Update notification sent to electron app {$id}",
        ];
    }

    /**
     * Rollback the app to its last saved git commit.
     */
    public function rollback(string $id): array
    {
        $path   = $this->registry->path($id);
        $lib    = config('ecosystem.lib');

        // Handle electron apps differently
        if ($this->registry->getType($id) === 'electron') {
            return $this->rollbackElectronApp($id);
        }

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
     * Rollback electron app
     */
    private function rollbackElectronApp(string $id): array
    {
        // For electron apps, we can't rollback directly since they run on user machines
        // Instead, we update the configuration to prompt users to rollback
        $app = \App\Models\AppModule::where('slug', $id)->first();
        if ($app) {
            $app->update([
                'config' => array_merge(
                    json_decode($app->config ?? '{}', true) ?: [],
                    ['needs_update' => true, 'update_available' => false, 'rollback_required' => true]
                )
            ]);
        }

        return [
            'success' => true,
            'output' => "Rollback notification sent to electron app {$id}",
        ];
    }

    /**
     * Run database migrations for an app.
     */
    public function migrate(string $id): array
    {
        // Electron apps don't have database migrations
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support database migrations");
        }

        return $this->artisan($id, 'migrate --force');
    }

    /**
     * Run a backup for an app.
     */
    public function backup(string $id): array
    {
        // Electron apps don't support server-side backups
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support server-side backups");
        }

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
        // Electron apps don't support artisan commands
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support artisan commands");
        }

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
        // Electron apps don't support git pulls
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support git operations");
        }

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
        // Electron apps don't support cache rebuilding
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support cache operations");
        }

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
        // Electron apps don't support maintenance mode
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support maintenance mode");
        }

        return $this->artisan($id, $down ? 'down --retry=30' : 'up');
    }

    /**
     * Restart queue workers for a service
     */
    public function restartQueue(string $id): array
    {
        // Electron apps don't support queue operations
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support queue operations");
        }

        return $this->artisan($id, 'queue:restart');
    }

    /**
     * Clear all caches for a service
     */
    public function clearCache(string $id): array
    {
        // Electron apps don't support cache clearing
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support cache operations");
        }

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
        // Electron apps don't support artisan sequences
        if ($this->registry->getType($id) === 'electron') {
            return $this->fail("Electron apps don't support artisan operations");
        }

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