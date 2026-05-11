<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SavedCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Exception;

class SecurityController extends Controller
{
    /**
     * Display advanced security overview.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $sessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->get()
                ->map(function ($session) {
                    $ua = $session->user_agent ?? '';
                    $os = 'Unknown OS';
                    $browser = 'Unknown Browser';

                    if (str_contains($ua, 'Windows'))
                        $os = 'Windows';
                    elseif (str_contains($ua, 'Macintosh'))
                        $os = 'macOS';
                    elseif (str_contains($ua, 'Android'))
                        $os = 'Android';
                    elseif (str_contains($ua, 'iPhone'))
                        $os = 'iOS';

                    if (str_contains($ua, 'Chrome'))
                        $browser = 'Chrome';
                    elseif (str_contains($ua, 'Firefox'))
                        $browser = 'Firefox';
                    elseif (str_contains($ua, 'Safari'))
                        $browser = 'Safari';

                    return [
                        'id' => $session->id,
                        'is_current' => $session->id === session()->getId(),
                        'ip_address' => $session->ip_address,
                        'os' => $os,
                        'browser' => $browser,
                        'last_active' => date('Y-m-d H:i:s', $session->last_activity),
                    ];
                });

            $external_apps = $user->tokens->map(function ($token) {
                return [
                    'name' => $token->name,
                    'last_used' => $token->last_used_at?->format('d M Y'),
                    'created_at' => $token->created_at?->format('d M Y'),
                ];
            });

            return Inertia::render('Security', [
                'sessions' => $sessions,
                'externalApps' => $external_apps,
                'securityCheckCompletedAt' => $user->security_check_completed_at,
            ]);
        } catch (Exception $e) {
            Log::error('Security index error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load security settings.');
        }
    }

    /**
     * Show 2FA Setup page.
     */
    public function show2faSetup(Request $request)
    {
        try {
            $user = $request->user();

            // Check if 2FA packages are available
            if (!class_exists('\Pragmas\Google2FALaravel\Google2FA')) {
                return back()->with('error', '2FA is not available. Contact administrator.');
            }

            $google2fa = new \Pragmas\Google2FALaravel\Google2FA();

            if (!$user->google2fa_secret) {
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->save();
            }

            $qrCodeUrl = $google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $user->google2fa_secret
            );

            $qrCodeSvg = null;
            if (class_exists('\BaconQrCode\Renderer\ImageRenderer')) {
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $writer = new \BaconQrCode\Writer($renderer);
                $qrCodeSvg = $writer->writeString($qrCodeUrl);
            }

            return Inertia::render('TwoFactorSetup', [
                'qrCodeSvg' => $qrCodeSvg,
                'secret' => $user->google2fa_secret,
            ]);
        } catch (Exception $e) {
            Log::error('2FA setup error: ' . $e->getMessage());
            return back()->with('error', 'Failed to setup 2FA.');
        }
    }

    /**
     * Confirm 2FA Setup.
     */
    public function confirm2faSetup(Request $request)
    {
        try {
            $request->validate(['code' => 'required|string']);
            $user = $request->user();

            if (!class_exists('\Pragmas\Google2FALaravel\Google2FA')) {
                return back()->withErrors(['code' => '2FA is not available.']);
            }

            $google2fa = new \Pragmas\Google2FALaravel\Google2FA();

            if ($google2fa->verifyKey($user->google2fa_secret, $request->code)) {
                $user->two_factor_enabled = true;
                $user->recovery_codes = encrypt(json_encode([
                    Str::random(10),
                    Str::random(10),
                    Str::random(10),
                    Str::random(10),
                    Str::random(10),
                    Str::random(10),
                ]));
                $user->save();

                ActivityLog::record(
                    $user->id,
                    'Two-factor authentication enabled',
                    '2FA',
                    '🛡️',
                    $request->ip()
                );

                return redirect()->route('security')->with('success', 'Two-factor authentication enabled.');
            }

            return back()->withErrors(['code' => 'Invalid verification code.']);
        } catch (Exception $e) {
            Log::error('2FA confirm error: ' . $e->getMessage());
            return back()->withErrors(['code' => 'Failed to confirm 2FA setup.']);
        }
    }

    /**
     * Disable 2FA.
     */
    public function disable2fa(Request $request)
    {
        try {
            $user = $request->user();
            $user->two_factor_enabled = false;
            $user->google2fa_secret = null;
            $user->recovery_codes = null;
            $user->save();

            ActivityLog::record($user->id, 'Two-factor authentication disabled', '2FA', '🛡️', $request->ip());

            return redirect()->back()->with('success', '2FA disabled.');
        } catch (Exception $e) {
            Log::error('2FA disable error: ' . $e->getMessage());
            return back()->with('error', 'Failed to disable 2FA.');
        }
    }

    /**
     * Terminate a specific session.
     */
    public function logoutSession(Request $request, $sessionId)
    {
        try {
            DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->where('id', $sessionId)
                ->delete();

            return redirect()->back()->with('success', 'Session terminated.');
        } catch (Exception $e) {
            Log::error('Session logout error: ' . $e->getMessage());
            return back()->with('error', 'Failed to terminate session.');
        }
    }

    /**
     * Password Manager - List saved credentials.
     */
    public function passwordManager(Request $request)
    {
        try {
            $credentials = SavedCredential::where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($cred) {
                    return [
                        'id' => $cred->id,
                        'site_name' => $cred->site_name,
                        'site_url' => $cred->site_url,
                        'username' => $cred->username,
                        'icon' => $cred->icon,
                        'created_at' => $cred->created_at?->format('d M Y'),
                    ];
                });

            return Inertia::render('PasswordManager', [
                'credentials' => $credentials,
            ]);
        } catch (Exception $e) {
            Log::error('Password manager error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load password manager.');
        }
    }

    /**
     * Decrypt and return a password for a credential.
     */
    public function getDecryptedPassword(Request $request, $id)
    {
        try {
            $cred = SavedCredential::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            return response()->json([
                'password' => Crypt::decryptString($cred->password)
            ]);
        } catch (Exception $e) {
            Log::error('Password decrypt error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to decrypt password.'], 500);
        }
    }

    /**
     * Save a new credential in the password manager.
     */
    public function saveCredential(Request $request)
    {
        try {
            $request->validate([
                'site_name' => 'required|string|max:255',
                'password' => 'required|string',
            ]);

            SavedCredential::create([
                'user_id' => $request->user()->id,
                'site_name' => $request->site_name,
                'site_url' => $request->site_url,
                'username' => $request->username,
                'password' => Crypt::encryptString($request->password),
                'icon' => $request->icon,
            ]);

            ActivityLog::record(
                $request->user()->id,
                'Saved new credential: ' . $request->site_name,
                'Password',
                '🔐',
                $request->ip()
            );

            return redirect()->back()->with('success', 'Credential saved.');
        } catch (Exception $e) {
            Log::error('Save credential error: ' . $e->getMessage());
            return back()->with('error', 'Failed to save credential.');
        }
    }

    /**
     * Delete a saved credential.
     */
    public function deleteCredential(Request $request, $id)
    {
        try {
            SavedCredential::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail()
                ->delete();

            return redirect()->back()->with('success', 'Credential deleted.');
        } catch (Exception $e) {
            Log::error('Delete credential error: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete credential.');
        }
    }
}
