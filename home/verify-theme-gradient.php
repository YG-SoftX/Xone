<?php
/**
 * Standalone verification script for HomeThemeService::getCssVariables().
 *
 * Tests that --yg-bg-gradient is generated when background_gradient is set,
 * and absent when it is not. Works without a database by injecting theme data
 * directly into the cache that HomeThemeService reads from.
 *
 * Usage: php verify-theme-gradient.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HomeThemeService;
use Illuminate\Support\Facades\Cache;

$service = app(HomeThemeService::class);
$passed = 0;
$failed = 0;

function test(string $description, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        echo "  ✅ {$description}\n";
        $passed++;
    } else {
        echo "  ❌ {$description}\n";
        $failed++;
    }
}

// ============================================================
echo "HomeThemeService — CSS Variable Verification\n";
echo str_repeat('=', 50) . "\n\n";

// ── Test 1: Gradient set → CSS variable appears ─────────────
echo "1) background_gradient SET:\n";

$service->clearCache();
Cache::put('home_theme_config', [
    'colors'   => ['primary' => '#2563eb', 'secondary' => '#7c3aed'],
    'fonts'    => ['heading_font' => 'Outfit', 'body_font' => 'Inter'],
    'logos'    => [],
    'settings' => ['border_radius' => 12],
    'name'     => 'Test Gradient',
    'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
], 60);

$css = $service->getCssVariables();
echo "   CSS output:\n";
foreach (explode("\n", trim($css)) as $line) {
    echo "     {$line}\n";
}
echo "\n";

test('Contains --yg-bg-gradient variable',    str_contains($css, '--yg-bg-gradient'));
test('Contains the gradient value',           str_contains($css, 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'));
test('Also contains normal color vars',       str_contains($css, '--yg-primary: #2563eb'));
test('Also contains font vars',               str_contains($css, '--font-heading'));

// ── Test 2: No gradient → no CSS variable ──────────────────
echo "\n2) background_gradient NOT SET (null):\n";

$service->clearCache();
Cache::put('home_theme_config', [
    'colors'   => ['primary' => '#2563eb'],
    'fonts'    => ['heading_font' => 'Outfit', 'body_font' => 'Inter'],
    'logos'    => [],
    'settings' => ['border_radius' => 12],
    'name'     => 'No Gradient',
    'background_gradient' => null,
], 60);

$css = $service->getCssVariables();
echo "   CSS output:\n";
foreach (explode("\n", trim($css)) as $line) {
    echo "     {$line}\n";
}
echo "\n";

test('Does NOT contain --yg-bg-gradient', !str_contains($css, '--yg-bg-gradient'));
test('Still generates other CSS vars',     str_contains($css, '--yg-primary: #2563eb'));

// ── Test 3: Empty string gradient → no CSS variable ────────
echo "\n3) background_gradient set to empty string:\n";

$service->clearCache();
Cache::put('home_theme_config', [
    'colors'   => ['primary' => '#2563eb'],
    'fonts'    => ['heading_font' => 'Outfit', 'body_font' => 'Inter'],
    'logos'    => [],
    'settings' => ['border_radius' => 12],
    'name'     => 'Empty Gradient',
    'background_gradient' => '',
], 60);

$css = $service->getCssVariables();

test('Does NOT contain --yg-bg-gradient when empty', !str_contains($css, '--yg-bg-gradient'));

// ── Results ────────────────────────────────────────────────
echo "\n" . str_repeat('=', 50) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo str_repeat('=', 50) . "\n\n";

$service->clearCache();
exit($failed > 0 ? 1 : 0);
