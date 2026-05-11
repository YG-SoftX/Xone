<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_reset_link_is_sent_for_existing_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_no_error_revealed_for_unknown_email(): void
    {
        // Prevents user enumeration — should still show status, not error
        $this->post('/forgot-password', ['email' => 'ghost@example.com'])
            ->assertSessionHas('status');
    }

    public function test_reset_password_page_renders_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/' . $notification->token)->assertStatus(200);
            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])->assertRedirect('/login');

            return true;
        });
    }
}
