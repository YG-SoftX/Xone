<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\ActivityLog;

class SecuritySettingsController extends Controller
{
    /**
     * Display security settings page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get active sessions
        $sessions = collect(Session::all())
            ->filter(function($value, $key) {
                return str_starts_with($key, 'login_');
            })
            ->map(function($value, $key) {
                return [
                    'id' => $key,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'last_active' => now(),
                    'is_current' => true
                ];
            })->values();
        
        // Get 2FA status
        $twoFactorEnabled = $user->two_factor_status;
        
        // Get login history
        $loginHistory = \DB::table('login_history')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('settings.security.index', compact('user', 'sessions', 'twoFactorEnabled', 'loginHistory'));
    }
    
    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        ActivityLog::record($user->id, 'Changed account password', 'Security', '🔑', $request->ip());
        
        return back()->with('success', 'Password updated successfully.');
    }
    
    /**
     * Enable Two-Factor Authentication - Step 1: Generate
     */
    public function enableTwoFactor()
    {
        $user = Auth::user();
        $google2fa = new Google2FA();
        
        if (!$user->two_factor_status) {
            // Generate secret if not exists
            if (!$user->two_factor_secret) {
                $user->two_factor_secret = $google2fa->generateSecretKey();
                $user->save();
            }

            // Generate QR Code as SVG
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                'YGXone Ecosystem',
                $user->email,
                $user->two_factor_secret
            );

            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $qrCodeSvg = $writer->writeString($qrCodeUrl);

            return response()->json([
                'success' => true,
                'qr_code_svg' => $qrCodeSvg,
                'secret' => $user->two_factor_secret,
            ]);
        }
        
        return response()->json(['success' => false, 'error' => '2FA already enabled']);
    }
    
    /**
     * Verify and Activate 2FA - Step 2: Confirm
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6'
        ]);
        
        $user = Auth::user();
        $google2fa = new Google2FA();
        
        $isValid = $google2fa->verifyKey($user->two_factor_secret, $request->code);
        
        if ($isValid) {
            $user->two_factor_status = true;
            $user->two_factor_verified = true;
            $user->save();

            ActivityLog::record($user->id, 'Activated Two-Factor Authentication', 'Security', '🛡️', $request->ip());

            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false, 'error' => 'Invalid verification code. Please try again.']);
    }
    
    /**
     * Disable Two-Factor Authentication
     */
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password'
        ]);

        $user = Auth::user();
        $user->two_factor_status = false;
        $user->two_factor_verified = false;
        $user->two_factor_secret = null;
        $user->save();
        
        ActivityLog::record($user->id, 'Deactivated Two-Factor Authentication', 'Security', '🔓', $request->ip());
        
        return back()->with('success', 'Two-factor authentication disabled.');
    }
    
    /**
     * Terminate other sessions
     */
    public function terminateSessions(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password'
        ]);
        
        Auth::logoutOtherDevices($request->password);
        
        ActivityLog::record(Auth::id(), 'Terminated other active sessions', 'Security', '🖥️', $request->ip());
        
        return back()->with('success', 'All other sessions have been terminated.');
    }
}
