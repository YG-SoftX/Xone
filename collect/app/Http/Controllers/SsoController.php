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
    public function initiate(Request $request)
    {
        $accountUrl = config('services.yg_account.url', 'https://account.ygxone.com');
        $callback = url('/sso/callback');
        $service = 'YG Collect';

        $queryParams = http_build_query([
            'service' => $service,
            'callback' => $callback,
        ]);

        return redirect($accountUrl . '/sso/initiate?' . $queryParams);
    }

    public function callback(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect('/')->withErrors(['sso' => 'No SSO token provided.']);
        }

        $accountUrl = config('services.yg_account.url', 'https://account.ygxone.com');
        $validateUrl = $accountUrl . '/api/sso/validate?token=' . $token;

        $response = $this->callAccountApi($validateUrl);

        if (!$response || isset($response['error'])) {
            Log::warning('SSO token validation failed', [
                'error' => $response['error'] ?? 'Unknown error',
            ]);

            return redirect('/')->withErrors([
                'sso' => 'SSO session expired. Please sign in again.',
            ]);
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
                $user->name = $response['name'];
                $user->save();
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect('/admin');
        } catch (\Exception $e) {
            Log::error('SSO user creation failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['sso' => 'Failed to synchronize identity.']);
        }
    }

    private function callAccountApi(string $url): ?array
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => !app()->isLocal(),
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);

            return $body ? json_decode($body, true) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
