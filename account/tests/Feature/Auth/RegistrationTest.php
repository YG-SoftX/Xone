<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_registration_page_renders(): void
    {
        $this->get('/register/individual')->assertStatus(200);
    }

    public function test_business_registration_page_renders(): void
    {
        $this->get('/register/business')->assertStatus(200);
    }

    public function test_legacy_register_get_redirects_to_individual(): void
    {
        $this->get('/register')->assertRedirect('/register/individual');
    }

    public function test_legacy_register_post_redirects_to_individual(): void
    {
        $this->post('/register')->assertRedirect('/register/individual');
    }

    public function test_new_individual_user_can_register(): void
    {
        $this->post('/register/individual', [
            'name'                  => 'Test User',
            'email'                 => 'newuser@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_registration_requires_valid_email(): void
    {
        $this->post('/register/individual', [
            'name'                  => 'Test User',
            'email'                 => 'not-an-email',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('email');
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $this->post('/register/individual', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'DifferentPassword!',
        ])->assertSessionHasErrors('password');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register/individual', [
            'name'                  => 'Another User',
            'email'                 => 'taken@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_cannot_access_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/register/individual')
            ->assertRedirect();
    }
}
