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
    /**
     * Called by YG Account (or any third-party app) after login with a one-time token.
     * Validates the token, finds or creates a local user, and starts a session.
     * 
     * Supports optional client_id for audit logging on the YG Account side.
     */
    public function callback(Request $request)
    {
        $token = $request->query('token');
        $clientId = $request->query('client_id'); // Optional: for audit logging

        if (!$token) {
            return redirect('/')->withErrors(['sso' => 'No SSO token provided.']);
        }

        // Validate token with YG Account using config (not env)
        $accountUrl = config('services.yg_account.url', 'http://localhost:8000');
        $validateUrl = $accountUrl . '/api/sso/validate?token=' . $token;
        if ($clientId) {
            $validateUrl .= '&client_id=' . urlencode($clientId);
        }

        $response = $this->callAccountApi($validateUrl);

        if (!$response || isset($response['error'])) {
            Log::warning('SSO token validation failed', [
                'error' => $response['error'] ?? 'Unknown error',
                'account_url' => $accountUrl,
                'client_id' => $clientId,
            ]);

            return redirect('/')->withErrors([
                'sso' => 'SSO session expired. Please sign in again.',
            ]);
        }

        try {
            // Find or create local user in YG Mail's own DB
            $user = User::firstOrCreate(
                ['email' => $response['email']],
                [
                    'name' => $response['name'],
                    'password' => Hash::make(Str::random(32)), // random password — auth via SSO only
                ]
            );

            // Sync name in case it changed
            if ($user->name !== $response['name']) {
                $user->name = $response['name'];
                $user->save();
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('SSO user creation failed', [
                'error' => $e->getMessage(),
                'email' => $response['email'] ?? 'unknown',
            ]);

            return redirect('/')->withErrors([
                'sso' => 'Failed to create account. Please try again or contact support.',
            ]);
        }
    }

    private function callAccountApi(string $url): ?array
    {
        $maxRetries = 2;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                // Try cURL first (more reliable on cPanel)
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 5,
                        CURLOPT_SSL_VERIFYPEER => !app()->isLocal(),
                        CURLOPT_FOLLOWLOCATION => true,
                    ]);
                    $body = curl_exec($ch);
                    $err = curl_error($ch);
                    curl_close($ch);

                    if ($body === false || $err) {
                        Log::warning("SSO cURL request failed (attempt {$attempt})", ['error' => $err]);
                        if ($attempt >= $maxRetries)
                            return null;
                        continue;
                    }

                    $data = json_decode($body, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('SSO response is not valid JSON', ['error' => json_last_error_msg()]);
                        return null;
                    }
                    return $data;
                }

                // Fallback to file_get_contents
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 5,
                        'ignore_errors' => true,
                    ],
                ]);
                $body = @file_get_contents($url, false, $context);

                if ($body === false) {
                    Log::warning("SSO file_get_contents failed (attempt {$attempt})");
                    if ($attempt >= $maxRetries)
                        return null;
                    continue;
                }

                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return null;
                }
                return $data;
            } catch (\Exception $e) {
                Log::warning("SSO API call exception (attempt {$attempt})", ['error' => $e->getMessage()]);
                if ($attempt >= $maxRetries)
                    return null;
            }
        }

        return null;
    }
}
