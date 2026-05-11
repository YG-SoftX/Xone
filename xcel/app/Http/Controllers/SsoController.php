<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function callback(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect('/')->withErrors(['sso' => 'No SSO token.']);
        }

        $data = $this->validateToken($token);

        if (!$data || isset($data['error'])) {
            Log::warning('Xcel SSO validation failed', ['error' => $data['error'] ?? 'Unknown']);
            return redirect('/')->withErrors(['sso' => 'SSO token expired or invalid. Please sign in again.']);
        }

        try {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make(Str::random(32))]
            );

            if ($user->name !== $data['name']) {
                $user->update(['name' => $data['name']]);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Xcel SSO user provisioning failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['sso' => 'Failed to create local account.']);
        }
    }

    /**
     * Call the YG Account SSO validation endpoint using the Http facade (Guzzle).
     * Retries once on failure. SSL verification follows the app environment.
     */
    private function validateToken(string $token): ?array
    {
        $url = config('services.yg_account.url', 'http://localhost:8000')
            . '/api/sso/validate?token=' . urlencode($token);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::timeout(5)
                    ->retry(1, 200)
                    ->get($url);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning("Xcel SSO attempt {$attempt} returned HTTP {$response->status()}");
            } catch (\Exception $e) {
                Log::warning("Xcel SSO attempt {$attempt} failed", ['error' => $e->getMessage()]);
            }
        }

        return null;
    }
}
