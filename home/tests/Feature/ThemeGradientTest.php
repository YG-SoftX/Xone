<?php

namespace Tests\Feature;

use App\Services\HomeThemeService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verify that HomeThemeService generates the --yg-bg-gradient CSS variable
 * when the theme has background_gradient set in the database.
 */
class ThemeGradientTest extends TestCase
{
    use WithFaker;

    private HomeThemeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HomeThemeService::class);
    }

    /** @test */
    public function it_generates_bg_gradient_css_variable_when_gradient_is_set()
    {
        // Clear any cached theme
        $this->service->clearCache();

        // Insert a test theme with background_gradient into the database
        $id = DB::table('themes')->insertGetId([
            'service'             => 'yg-xone',
            'name'                => 'Test Gradient',
            'is_active'           => true,
            'colors'              => json_encode(['primary' => '#2563eb']),
            'fonts'               => json_encode(['heading_font' => 'Outfit', 'body_font' => 'Inter']),
            'logos'               => json_encode([]),
            'settings'            => json_encode(['border_radius' => 12]),
            'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Clear the cache so the service re-fetches from the DB
        $this->service->clearCache();

        $css = $this->service->getCssVariables();

        // Clean up
        DB::table('themes')->where('id', $id)->delete();
        $this->service->clearCache();

        // Assert the CSS variable is present
        $this->assertStringContainsString(
            '--yg-bg-gradient',
            $css,
            'CSS output should contain --yg-bg-gradient variable'
        );

        $this->assertStringContainsString(
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            $css,
            'CSS output should contain the actual gradient value'
        );
    }

    /** @test */
    public function it_does_not_generate_bg_gradient_css_when_no_gradient_is_set()
    {
        $this->service->clearCache();

        // Insert a theme WITHOUT background_gradient
        $id = DB::table('themes')->insertGetId([
            'service'   => 'yg-xone',
            'name'      => 'No Gradient',
            'is_active' => true,
            'colors'    => json_encode(['primary' => '#2563eb']),
            'fonts'     => json_encode(['heading_font' => 'Outfit', 'body_font' => 'Inter']),
            'logos'     => json_encode([]),
            'settings'  => json_encode(['border_radius' => 12]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->clearCache();

        $css = $this->service->getCssVariables();

        DB::table('themes')->where('id', $id)->delete();
        $this->service->clearCache();

        // Should NOT contain --yg-bg-gradient
        $this->assertStringNotContainsString(
            '--yg-bg-gradient',
            $css,
            'CSS output should NOT contain --yg-bg-gradient when no gradient is set'
        );
    }
}
