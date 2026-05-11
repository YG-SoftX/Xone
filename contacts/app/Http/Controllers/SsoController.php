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
                ['name' => $data['name'], 'password' => Hash::make(Str::random(32))]
            );

            if ($user->name !== $data['name']) {
                $user->update(['name' => $data['name']]);
            }

            // Provision a primary calendar if none exists
            if (!Calendar::where('user_id', $user->id)->where('is_primary', true)->exists()) {
                Calendar::create([
                    'user_id'    => $user->id,
                    'name'       => $user->name . "'s Calendar",
                    'color'      => '#4285f4',
                    'is_primary' => true,
                    'type'       => 'personal',
                    'timezone'   => 'UTC',
                ]);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended('/');
        } catch (\Exception $e) {
            Log::error('Contacts SSO failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['sso' => 'Authentication failed.']);
        }
    }

    private function validateToken(string $token): ?array
    {
        $url = config('services.yg_account.url', 'http://localhost:8000')
            . '/api/sso/validate?token=' . urlencode($token);

        try {
            $response = Http::timeout(5)->retry(1, 200)->get($url);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('Contacts SSO validation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
