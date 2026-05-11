<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_page_requires_authentication(): void
    {
        $this->get('/verify-security')->assertRedirect('/login');
    }

    public function test_otp_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/verify-security')
            ->assertStatus(200);
    }

    public function test_valid_otp_passes_verification(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $code = '123456';

        // Seed the OTP in session the same way OtpService stores it
        $this->actingAs($user)
            ->withSession(['otp_code' => $code, 'otp_generated_at' => now()->timestamp])
            ->post('/verify-security', ['otp' => $code])
            ->assertRedirect();

        $this->assertTrue(session('otp_verified') ?? false);
    }

    public function test_invalid_otp_fails_verification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['otp_code' => '999999', 'otp_generated_at' => now()->timestamp])
            ->post('/verify-security', ['otp' => '000000'])
            ->assertSessionHasErrors();
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->create();
        $expiredTimestamp = now()->subMinutes(11)->timestamp;

        $this->actingAs($user)
            ->withSession(['otp_code' => '123456', 'otp_generated_at' => $expiredTimestamp])
            ->post('/verify-security', ['otp' => '123456'])
            ->assertSessionHasErrors();
    }

    public function test_otp_protected_route_redirects_without_verification(): void
    {
        $user = User::factory()->create();

        // /pay requires otp.verified middleware
        $this->actingAs($user)
            ->get('/pay')
            ->assertRedirect('/verify-security');
    }
}
