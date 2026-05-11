<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    public function callback(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect('/')->withErrors(['sso' => 'No SSO token provided.']);
        }

        $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
        $response = $this->callAccountApi($accountUrl . '/api/sso/validate?token=' . $token);

        if (!$response || isset($response['error'])) {
            Log::warning('DocX SSO token validation failed', ['error' => $response['error'] ?? 'Unknown']);
            return redirect('/')->withErrors(['sso' => 'SSO session expired. Please sign in again.']);
        }

        try {
            $user = User::firstOrCreate(
                ['email' => $response['email']],
                [
                    'name' => $response['name'],
                    'password' => Hash::make(Str::random(32)),
                ]
            );

            if ($user->name !== $response['name']) {
                $user->update(['name' => $response['name']]);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('DocX SSO user creation failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['sso' => 'Failed to create account. Please try again.']);
        }
    }

    private function callAccountApi(string $url): ?array
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 5,
                        CURLOPT_SSL_VERIFYPEER => !app()->isLocal(),
                    ]);
                    $body = curl_exec($ch);
                    curl_close($ch);
                    if ($body) {
                        $data = json_decode($body, true);
                        if (json_last_error() === JSON_ERROR_NONE)
                            return $data;
                    }
                }
                $body = @file_get_contents($url);
                if ($body) {
                    $data = json_decode($body, true);
                    if (json_last_error() === JSON_ERROR_NONE)
                        return $data;
                }
            } catch (\Exception $e) {
                Log::warning("DocX SSO API attempt {$attempt} failed", ['error' => $e->getMessage()]);
            }
        }
        return null;
    }
}
