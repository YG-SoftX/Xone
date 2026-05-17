<?php

namespace App\Http\Controllers;

use App\Models\ThirdPartyApp;
use App\Models\ThirdPartyAuthLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Built-in allowed callback domains for internal YG services.
     * Third-party apps registered in the database will also be allowed.
     */
    const BUILTIN_CALLBACK_DOMAINS = [
        'mail.ygxone.com',
        'drive.ygxone.com',
        'docx.ygxone.com',
        'meet.ygxone.com',
        'chat.ygxone.com',
        'pay.ygxone.com',
        'master.ygxone.com',
        'ygxone.com',
        'www.ygxone.com',
        'ygsoftx.com',
        'www.ygsoftx.com',
        'localhost',
        '127.0.0.1',
    ];

    /**
     * Initiate SSO flow from an external YG service.
     * e.g. GET /sso/initiate?service=YG+Mail&callback=https://mail.ygxone.com/sso/callback&client_id=xxx
     */
    public function initiate(Request $request)
    {
        $service = $request->query('service', 'YG Ecosystem');
        $callback = $request->query('callback');
        $clientId = $request->query('client_id');

        if ($callback) {
            // Validate callback against allowlist
            if (!$this->isAllowedCallback($callback)) {
                return abort(403, 'Unauthorized callback domain. Only registered YG services and third-party apps can request SSO tokens.');
            }

            session(['sso_callback' => $callback, 'sso_service' => $service]);

            // Log the SSO initiation event if client_id is provided
            if ($clientId) {
                $app = ThirdPartyApp::where('client_id', $clientId)->first();
                if ($app) {
                    $app->recordAuthEvent(
                        'sso_initiated',
                        null,
                        $request->ip(),
                        $request->userAgent(),
                        ['service' => $service, 'callback' => $callback]
                    );
                }
            }
        }

        // If already authenticated, issue token and redirect immediately
        if (auth()->check()) {
            return $this->issueTokenAndRedirect(auth()->user(), $callback);
        }

        return redirect()->route('login');
    }

    /**
     * Validate a callback URL against builtin domains AND registered third-party apps.
     */
    protected function isAllowedCallback(string $callbackUrl): bool
    {
        $parsed = parse_url($callbackUrl);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }

        $domain = strtolower($parsed['host']);

        // Check builtin domains
        foreach (self::BUILTIN_CALLBACK_DOMAINS as $allowed) {
            if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
                return true;
            }
        }

        // Check registered third-party apps
        $registeredApp = ThirdPartyApp::where('redirect_uri', $callbackUrl)
            ->orWhere(function ($query) use ($domain) {
                $query->where('is_active', true)
                    ->whereRaw("LOWER(redirect_uri) LIKE ?", ['%://' . $domain . '/%']);
            })
            ->where('is_active', true)
            ->first();

        return $registeredApp !== null;
    }

    /**
     * Called by external YG apps to validate the one-time SSO token.
     * GET /api/sso/validate?token=xxx&client_id=yyy (optional)
     */
    public function validateToken(Request $request)
    {
        $token = $request->query('token');
        $clientId = $request->query('client_id');

        if (!$token) {
            return response()->json(['error' => 'No token provided'], 400);
        }

        $encryptedData = Cache::pull("sso_token_{$token}");

        if (!$encryptedData) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        try {
            $data = Crypt::decryptString($encryptedData);
            $userData = json_decode($data, true);

        if ($clientId) {
            $app = ThirdPartyApp::where('client_id', $clientId)->first();

            if ($app) {
                if (!$app->is_active) {
                    return response()->json(['error' => 'App is deactivated'], 403);
                }

                // Accept secret via header only — never query params (they land in logs)
                $clientSecret = $request->header('X-Client-Secret');
                if (!$clientSecret || !hash_equals((string) $app->client_secret, $clientSecret)) {
                    return response()->json(['error' => 'Invalid client secret.'], 401);
                }

                $app->recordAuthEvent(
                    'sso_validated',
                    (int) $userData['id'],
                    $request->ip(),
                    $request->userAgent(),
                    ['token_used' => $token]
                );
            }
        }

            return response()->json([
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token data'], 401);
        }
    }

    /**
     * Generate a one-time token and redirect to the callback URL.
     *
     * The callback domain is re-validated here as defense-in-depth — callers should
     * also validate upstream, but this method is the final gate before a redirect is issued.
     */
    public function issueTokenAndRedirect($user, $callbackUrl)
    {
        if (!$callbackUrl) {
            return redirect()->route('dashboard');
        }

        if (!$this->isAllowedCallback($callbackUrl)) {
            abort(403, 'Unauthorized SSO callback domain.');
        }

        $token    = Str::random(64);
        $userData = json_encode([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);

        Cache::put("sso_token_{$token}", Crypt::encryptString($userData), 120);

        return redirect($callbackUrl . '?token=' . $token);
    }
}
