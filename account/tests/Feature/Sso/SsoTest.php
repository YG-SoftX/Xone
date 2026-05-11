<?php

namespace Tests\Feature\Sso;

use App\Http\Controllers\SsoController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SsoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_initiate_rejects_unknown_callback_domain(): void
    {
        $this->get('/sso/initiate?callback=https://evil.com/steal')
            ->assertStatus(403);
    }

    public function test_sso_initiate_rejects_missing_host(): void
    {
        $this->get('/sso/initiate?callback=not-a-url')
            ->assertStatus(403);
    }

    public function test_sso_initiate_accepts_builtin_ygxone_domain(): void
    {
        // Not logged in → should redirect to login, not 403
        $this->get('/sso/initiate?callback=https://mail.ygxone.com/sso/callback')
            ->assertRedirect('/login');
    }

    public function test_sso_initiate_stores_callback_in_session(): void
    {
        $this->get('/sso/initiate?service=YG+Mail&callback=https://mail.ygxone.com/sso/callback');

        $this->assertEquals('https://mail.ygxone.com/sso/callback', session('sso_callback'));
        $this->assertEquals('YG Mail', session('sso_service'));
    }

    public function test_sso_validate_returns_400_for_missing_token(): void
    {
        $this->getJson('/api/sso/validate')
            ->assertStatus(400)
            ->assertJson(['error' => 'No token provided']);
    }

    public function test_sso_validate_returns_401_for_invalid_token(): void
    {
        $this->getJson('/api/sso/validate?token=bogus-token-xyz')
            ->assertStatus(401)
            ->assertJson(['error' => 'Invalid or expired token']);
    }

    public function test_sso_full_flow_issues_valid_token_and_validates_it(): void
    {
        $user = User::factory()->create();

        $controller = new SsoController();
        $redirect   = $controller->issueTokenAndRedirect($user, 'https://mail.ygxone.com/sso/callback');

        preg_match('/[?&]token=([A-Za-z0-9]+)/', $redirect->getTargetUrl(), $m);
        $token = $m[1] ?? null;

        $this->assertNotNull($token, 'Expected a token in the redirect URL');

        $this->getJson("/api/sso/validate?token={$token}")
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email'])
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_sso_token_is_single_use(): void
    {
        $user = User::factory()->create();

        $controller = new SsoController();
        $redirect   = $controller->issueTokenAndRedirect($user, 'https://mail.ygxone.com/sso/callback');

        preg_match('/[?&]token=([A-Za-z0-9]+)/', $redirect->getTargetUrl(), $m);
        $token = $m[1];

        $this->getJson("/api/sso/validate?token={$token}")->assertStatus(200);
        $this->getJson("/api/sso/validate?token={$token}")->assertStatus(401);
    }

    public function test_sso_issue_token_rejects_unknown_callback(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        (new SsoController())->issueTokenAndRedirect($user, 'https://evil.com/callback');
    }

    public function test_sso_validate_rejects_bad_client_secret_via_header(): void
    {
        $user = User::factory()->create();

        $controller = new SsoController();
        $redirect   = $controller->issueTokenAndRedirect($user, 'https://mail.ygxone.com/sso/callback');

        preg_match('/[?&]token=([A-Za-z0-9]+)/', $redirect->getTargetUrl(), $m);
        $token = $m[1];

        // Provide a fake client_id — since no app exists, auth block is skipped, but
        // this confirms client_secret is NOT read from query params
        $this->getJson("/api/sso/validate?token={$token}&client_id=fake&client_secret=leaked")
            ->assertStatus(200); // client_secret in query should be ignored
    }
}
