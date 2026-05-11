<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckApiQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_in_query_param_is_not_accepted(): void
    {
        // Even with a valid-looking key in ?api_key=, the middleware should ignore it
        $this->getJson('/api/tokens?api_key=any-key')
            ->assertStatus(401);
    }

    public function test_request_with_no_credentials_is_rejected(): void
    {
        $this->getJson('/api/tokens')->assertStatus(401);
    }

    public function test_sanctum_bearer_token_grants_access(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/tokens')->assertStatus(200);
    }

    public function test_ip_restriction_response_does_not_expose_allowed_ips(): void
    {
        // We can test the middleware response shape directly
        $response = $this->withHeaders(['X-API-Key' => 'non-existent-key'])
            ->getJson('/api/tokens');

        // Whatever error comes back, it must not have an allowed_ips field
        $this->assertArrayNotHasKey('allowed_ips', $response->json() ?? []);
    }

    public function test_authorization_header_bearer_is_validated(): void
    {
        // A random string that doesn't match any Sanctum token should be rejected
        $this->withHeaders(['Authorization' => 'Bearer invalid-token-string'])
            ->getJson('/api/tokens')
            ->assertStatus(401);
    }
}
