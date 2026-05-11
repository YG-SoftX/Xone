<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')->assertStatus(200);
    }

    public function test_admin_login_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/admin/login', [
                'email'    => 'admin@example.com',
                'password' => 'wrong',
            ]);
        }

        $this->post('/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    public function test_admin_routes_require_admin_session(): void
    {
        // No session → should be rejected
        $this->get('/admin')->assertRedirect();
    }

    public function test_non_admin_user_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);
    }

    public function test_only_super_admin_can_assign_admin_role(): void
    {
        $regularAdmin = User::factory()->create(['role' => 'admin']);
        $target       = User::factory()->create(['role' => 'user']);

        $this->withSession(['admin_user_id' => $regularAdmin->id])
            ->patch("/admin/users/{$target->id}/role", ['role' => 'admin'])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Role must not have changed
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'user']);
    }

    public function test_super_admin_can_assign_admin_role(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target     = User::factory()->create(['role' => 'user']);

        $this->withSession(['admin_user_id' => $superAdmin->id])
            ->patch("/admin/users/{$target->id}/role", ['role' => 'admin'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'admin']);
    }

    public function test_admin_can_suspend_user(): void
    {
        $admin  = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'user']);

        $this->withSession(['admin_user_id' => $admin->id])
            ->patch("/admin/users/{$target->id}/status", ['status' => 'suspended'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'suspended']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->withSession(['admin_user_id' => $admin->id])
            ->delete("/admin/users/{$admin->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
