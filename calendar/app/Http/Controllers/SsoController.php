<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Calendar;

class SsoController extends Controller
{
    public function callback(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect('/')->withErrors(['sso' => 'No SSO token provided.']);
        }

        $data = $this->validateToken($token);

        if (!$data || isset($data['error'])) {
            return redirect('/')->withErrors(['sso' => 'SSO token expired. Please sign in again.']);
        }

        try {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name']]
            );
            if (!$user->password) {
                $user->password = Hash::make(Str::random(32));
                $user->save();
            }

            if ($user->name !== $data['name']) {
                $user->update(['name' => $data['name']]);
            }

            // Provision a primary calendar if none exists
            if (!Calendar::where('user_id', $user->id)->where('is_primary', true)->exists()) {
                $cal = new Calendar();
                $cal->user_id    = $user->id;
                $cal->name       = $user->name . "'s Calendar";
                $cal->color      = '#4285f4';
                $cal->is_primary = true;
                $cal->type       = 'personal';
                $cal->timezone   = 'UTC';
                $cal->save();
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Calendar SSO failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['sso' => 'Authentication failed.']);
        }
    }

    private function validateToken(string $token): ?array
    {
        // Validate token format before using in HTTP request
        if (!preg_match('/^[A-Za-z0-9\-_\.]+$/', $token) || strlen($token) > 512) {
            return null;
        }

        $url = rtrim(config('services.yg_account.url', 'http://localhost:8000'), '/')
            . '/api/sso/validate';

        try {
            $response = Http::timeout(5)->retry(1, 200)->get($url, ['token' => $token]);
            if ($response->successful()) {
                $data = $response->json();
                // Ensure required fields exist and are the right type
                if (!isset($data['email'], $data['name'])
                    || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
                    || !is_string($data['name'])) {
                    return null;
                }
                return $data;
            }
        } catch (\Exception $e) {
            Log::warning('Calendar SSO validation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
