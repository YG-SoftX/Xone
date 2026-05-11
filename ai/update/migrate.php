<?php
/**
 * Yuga — CLI migration runner
 *
 * Usage (SSH / cPanel Terminal):
 *   php update/migrate.php            — run pending migrations
 *   php update/migrate.php status     — show migration history
 *   php update/migrate.php pending    — list un-applied migrations
 */

define('YUGA_ROOT', dirname(__DIR__));

require_once YUGA_ROOT . '/core/Migrator.php';

$dataDir       = YUGA_ROOT . '/data';
$migrationsDir = YUGA_ROOT . '/update/migrations';

if (!is_dir($dataDir)) {
    echo "ERROR: data/ directory not found. Is YUGA_ROOT correct?\n";
    exit(1);
}

$migrator = new Migrator($dataDir, $migrationsDir);

$cmd = $argv[1] ?? 'run';

switch ($cmd) {

    case 'status':
        $history = $migrator->history();
        if (empty($history)) {
            echo "No migrations have been applied yet.\n";
        } else {
            echo str_pad('Migration', 40) . "  Applied At\n";
            echo str_repeat('-', 60) . "\n";
            foreach ($history as $row) {
                echo str_pad($row['name'], 40) . '  ' . date('Y-m-d H:i:s', $row['applied_at']) . "\n";
            }
        }
        $pending = count($migrator->pending());
        echo "\n$pending pending migration(s).\n";
        break;

    case 'pending':
        $pending = $migrator->pending();
        if (empty($pending)) {
            echo "No pending migrations.\n";
        } else {
            foreach ($pending as $f) {
                echo '  • ' . basename($f) . "\n";
            }
        }
        break;

    case 'run':
    default:
        echo "Yuga Migrator\n";
        echo "=============\n";
        if (!$migrator->hasPending()) {
            echo "Nothing to do — all migrations are up to date.\n";
            exit(0);
        }

        $applied = $migrator->run(function ($msg) {
            echo $msg . "\n";
        });

        echo "\nDone. Applied " . count($applied) . " migration(s).\n";
        break;
}
