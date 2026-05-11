<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    /**
     * Validate credentials and return the authenticated admin User.
     *
     * Enforces rate-limiting, credential verification, and admin-role checks
     * before granting access. Throws ValidationException on any failure so
     * controllers can let Laravel handle the error response automatically.
     *
     * @throws ValidationException
     */
    public function attempt(string $email, string $password, string $ip): User
    {
        $throttleKey = $this->throttleKey($email, $ip);

        $this->ensureNotRateLimited($throttleKey);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, config('admin.lockout_minutes', 15) * 60);
            Log::warning('Failed admin login attempt', ['email' => $email, 'ip' => $ip]);

            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if (!$this->isAdmin($user)) {
            RateLimiter::hit($throttleKey, config('admin.lockout_minutes', 15) * 60);
            Log::warning('Unauthorized admin access attempt', ['email' => $email, 'ip' => $ip]);

            throw ValidationException::withMessages([
                'email' => 'Unauthorized access.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        Log::info('Admin login successful', ['user_id' => $user->id, 'ip' => $ip]);

        return $user;
    }

    /**
     * Determine whether a user holds admin privileges.
     */
    public function isAdmin(User $user): bool
    {
        $adminEmails = array_map('trim', explode(',', config('admin.emails', 'admin@ygxone.com')));

        return $user->id === 1 || in_array($user->email, $adminEmails);
    }

    /**
     * @throws ValidationException
     */
    private function ensureNotRateLimited(string $throttleKey): void
    {
        $maxAttempts = config('admin.max_login_attempts', 5);

        if (!RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(string $email, string $ip): string
    {
        return 'admin_login:' . Str::lower($email) . '|' . $ip;
    }
}
