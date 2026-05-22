<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SSOController extends Controller
{
    /**
     * Initiate SSO by redirecting to the YG Account service.
     *
     * The account service's /sso/initiate expects:
     *   - service   : display name shown on the login page
     *   - callback  : URL to return the one-time token to (THIS endpoint's /sso/callback)
     */
    public function initiate(Request $request)
    {
        $accountUrl  = Config::get('services.yg_account.url', 'https://account.ygxone.com');
        $callbackUrl = route('sso.callback');   // https://ygxone.com/sso/callback

        $params = http_build_query([
            'service'  => 'YG Home (ygxone.com)',
            'callback' => $callbackUrl,
        ]);

        return redirect("{$accountUrl}/sso/initiate?{$params}");
    }

    /**
     * Handle the SSO callback from YG Account service.
     *
     * The account service redirects here with ?token=<one-time-token>.
     * We validate that token against account.ygxone.com/api/sso/validate,
     * store the user info in the session, and redirect to the home page.
     */
    public function callback(Request $request)
    {
        $token = $request->query('token');

        if (! $token) {
            Log::warning('SSO callback received without a token.');
            return redirect()->route('browser.home')
                ->with('error', 'Login failed: no token received. Please try again.');
        }

        $accountApiUrl = Config::get('services.yg_account.url', 'https://account.ygxone.com');

        try {
            $response = Http::timeout(10)
                ->get("{$accountApiUrl}/api/sso/validate", [
                    'token' => $token,
                ]);

            if ($response->successful()) {
                $user = $response->json();

                // Store the authenticated user in the session so views know who is logged in
                Session::put('sso_user', [
                    'id'    => $user['id']    ?? null,
                    'name'  => $user['name']  ?? 'YG User',
                    'email' => $user['email'] ?? '',
                ]);

                // Natively log the user in locally since both applications share the database
                if (!empty($user['id'])) {
                    Auth::loginUsingId($user['id'], true);
                    
                    // Verify the login was successful
                    if (!Auth::check()) {
                        Log::error('SSO: Auth::loginUsingId() failed to authenticate user', [
                            'user_id' => $user['id'],
                        ]);
                    } else {
                        Log::info('SSO: User successfully authenticated via Auth::loginUsingId', [
                            'user_id' => $user['id'],
                            'auth_id' => Auth::id(),
                        ]);
                    }
                }

                Log::info('SSO login successful', [
                    'user_id' => $user['id'] ?? null,
                    'auth_check' => Auth::check(),
                    'session_has_sso_user' => Session::has('sso_user'),
                ]);

                return redirect()->route('browser.home')
                    ->with('sso_success', 'Welcome back, ' . ($user['name'] ?? 'User') . '!');
            }

            Log::warning('SSO token validation failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            return redirect()->route('browser.home')
                ->with('error', 'Login failed: invalid or expired token. Please try again.');

        } catch (\Exception $e) {
            Log::error('SSO callback exception', ['message' => $e->getMessage()]);

            return redirect()->route('browser.home')
                ->with('error', 'Login failed: could not reach account service. Please try again.');
        }
    }

    /**
     * Handle SSO logout — clear local session and redirect to account logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        Session::forget('sso_user');

        $accountUrl  = Config::get('services.yg_account.url', 'https://account.ygxone.com');
        $redirectUrl = $request->query('redirect', url('/'));

        return redirect("{$accountUrl}/sso/logout?redirect=" . urlencode($redirectUrl));
    }

    /**
     * Check if an SSO user is stored in session.
     */
    public static function ssoUser(): ?array
    {
        return Session::get('sso_user');
    }
}