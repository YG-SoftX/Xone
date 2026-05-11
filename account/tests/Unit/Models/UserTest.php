<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tests\TestCase;

class UserTest extends TestCase
{
    // ── Hidden field serialization ────────────────────────────────────────────

    public function test_password_is_hidden_in_serialization(): void
    {
        $user = new User();
        $user->forceFill(['password' => 'hashed']);

        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_google2fa_secret_is_hidden_in_serialization(): void
    {
        $user = new User();
        $user->forceFill(['google2fa_secret' => 'JBSWY3DPEHPK3PXP']);

        $this->assertArrayNotHasKey('google2fa_secret', $user->toArray());
    }

    public function test_recovery_codes_are_hidden_in_serialization(): void
    {
        $user = new User();
        $user->forceFill(['recovery_codes' => 'encrypted-codes']);

        $this->assertArrayNotHasKey('recovery_codes', $user->toArray());
    }

    public function test_remember_token_is_hidden_in_serialization(): void
    {
        $user = new User();
        $user->forceFill(['remember_token' => 'some-token']);

        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    // ── MustVerifyEmail ───────────────────────────────────────────────────────

    public function test_user_implements_must_verify_email(): void
    {
        $this->assertInstanceOf(MustVerifyEmail::class, new User());
    }

    // ── Rank attribute ────────────────────────────────────────────────────────

    public function test_rank_novice_below_100_stones(): void
    {
        $user = new User();
        $user->forceFill(['stones' => 50]);
        $this->assertEquals('Novice', $user->rank);
    }

    public function test_rank_contributor_at_100_stones(): void
    {
        $user = new User();
        $user->forceFill(['stones' => 100]);
        $this->assertEquals('Contributor', $user->rank);
    }

    public function test_rank_elite_at_500_stones(): void
    {
        $user = new User();
        $user->forceFill(['stones' => 500]);
        $this->assertEquals('Elite', $user->rank);
    }

    public function test_rank_grand_master_at_1000_stones(): void
    {
        $user = new User();
        $user->forceFill(['stones' => 1000]);
        $this->assertEquals('Grand Master', $user->rank);
    }

    public function test_rank_legend_at_5000_stones(): void
    {
        $user = new User();
        $user->forceFill(['stones' => 5000]);
        $this->assertEquals('Legend', $user->rank);
    }

    // ── Privileged setters do not use fillable ────────────────────────────────

    public function test_security_sensitive_fields_are_not_mass_assignable(): void
    {
        $fillable = (new User())->getFillable();

        foreach (['role', 'kyc_status', 'google2fa_secret', 'recovery_codes', 'two_factor_enabled'] as $field) {
            $this->assertNotContains($field, $fillable, "{$field} should not be mass-assignable");
        }
    }

    // ── getUserImageAttribute ─────────────────────────────────────────────────

    public function test_user_image_falls_back_to_avatar_service_when_no_image(): void
    {
        $user = new User();
        $user->forceFill(['name' => 'John Doe', 'image' => null]);

        $this->assertStringContainsString('ui-avatars.com', $user->user_image);
    }
}
