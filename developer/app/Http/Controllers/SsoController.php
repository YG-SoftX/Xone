<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    /**
     * Redirect the user to YG Account for SSO login.
     */
    public function redirect(): RedirectResponse
    {
        $accountUrl  = rtrim(config('services.yg_account.url'), '/');
        $callbackUrl = route('sso.callback');

        return redirect($accountUrl . '/sso/initiate?' . http_build_query([
            'callback' => $callbackUrl,
            'service'  => 'developer',
        ]));
    }

    /**
     * Handle the SSO callback — validate token, upsert local user, log in.
     */
    public function callback(Request $request): RedirectResponse
    {
        $token = $request->string('token')->value();

        if (empty($token)) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'SSO login failed: no token received.']);
        }

        $apiBase = rtrim(config('services.yg_account.api_base'), '/');

        try {
            $response = Http::timeout(10)
                ->get("{$apiBase}/sso/validate", ['token' => $token]);
        } catch (\Throwable $e) {
            Log::error('YG Account SSO validation failed', ['error' => $e->getMessage()]);
            return redirect()->route('login')
                ->withErrors(['sso' => 'Unable to reach YG Account. Please try again.']);
        }

        if (! $response->ok()) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'SSO token is invalid or has expired.']);
        }

        $ygUser = $response->json();

        if (empty($ygUser) || isset($ygUser['error']) || ! isset($ygUser['id'])) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'SSO token validation failed.']);
        }

        $user = User::updateOrCreate(
            ['yg_account_id' => $ygUser['id']],
            [
                'name'      => $ygUser['name'] ?? 'User',
                'email'     => $ygUser['email'],
                'api_token' => $token,
                // No local password — authentication is entirely via YG Account SSO
                'password'  => '',
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the developer out of the portal.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
