<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_impersonate(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->withSession(['admin_user_id' => $admin->id])
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(session('impersonating_user_id'));
    }

    public function test_super_admin_can_impersonate_regular_user(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target     = User::factory()->create(['role' => 'user']);

        $this->withSession(['admin_user_id' => $superAdmin->id])
            ->post("/admin/users/{$target->id}/impersonate")
            ->assertRedirect('/dashboard')
            ->assertSessionHas('info');

        $this->assertEquals($target->id, session('impersonating_user_id'));
    }

    public function test_cannot_impersonate_admin_user(): void
    {
        $superAdmin  = User::factory()->create(['role' => 'super_admin']);
        $adminTarget = User::factory()->create(['role' => 'admin']);

        $this->withSession(['admin_user_id' => $superAdmin->id])
            ->post("/admin/users/{$adminTarget->id}/impersonate")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(session('impersonating_user_id'));
    }

    public function test_cannot_impersonate_super_admin(): void
    {
        $superAdmin1 = User::factory()->create(['role' => 'super_admin']);
        $superAdmin2 = User::factory()->create(['role' => 'super_admin']);

        $this->withSession(['admin_user_id' => $superAdmin1->id])
            ->post("/admin/users/{$superAdmin2->id}/impersonate")
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_impersonation_session_stores_expiry(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target     = User::factory()->create(['role' => 'user']);
        $before     = time();

        $this->withSession(['admin_user_id' => $superAdmin->id])
            ->post("/admin/users/{$target->id}/impersonate");

        $expires = session('impersonation_expires');
        $this->assertGreaterThanOrEqual($before + 3600, $expires);
        $this->assertEquals($superAdmin->id, session('impersonation_admin_id'));
    }

    public function test_impersonation_is_audit_logged(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target     = User::factory()->create(['role' => 'user']);

        $this->withSession(['admin_user_id' => $superAdmin->id])
            ->post("/admin/users/{$target->id}/impersonate");

        $this->assertDatabaseHas('activity_logs', [
            'user_id'  => $superAdmin->id,
            'category' => 'Admin',
        ]);
    }
}
