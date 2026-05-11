<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class SsoService
{
    /**
     * Redirect to YG Account for authentication
     */
    public function redirectToProvider()
    {
        return Socialite::driver('yg_account')->redirect();
    }

    /**
     * Handle callback from YG Account
     */
    public function handleProviderCallback()
    {
        try {
            $ygUser = Socialite::driver('yg_account')->user();

            // Find or create user in local database
            $user = User::updateOrCreate(
                ['email' => $ygUser->getEmail()],
                [
                    'name' => $ygUser->getName(),
                    'yg_account_id' => $ygUser->getId(),
                    'avatar_url' => $ygUser->getAvatar(),
                    'email_verified_at' => now(),
                ]
            );

            // Login the user
            auth()->login($user, true);

            // Regenerate session to prevent session fixation
            session()->regenerate();

            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SSO login failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => 'Failed to authenticate with YG Account. Please try again.',
            ]);
        }
    }

    /**
     * Logout and redirect to YG Account logout
     */
    public function logout()
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->away(config('services.yg_account.url') . '/logout');
    }

    /**
     * Verify user token from YG Account
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.yg_account.client_secret'),
            ])->get(config('services.yg_account.url') . '/api/v1/token/verify', [
                'token' => $token,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Token verification failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get user profile from YG Account
     */
    public function getUserProfile(string $userId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.yg_account.client_secret'),
            ])->get(config('services.yg_account.url') . '/api/v1/users/' . $userId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to fetch user profile', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
