<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use App\Services\CrossModuleSyncService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'twoFactorEnabled' => $request->user()->two_factor_status,
        ]);
    }

    /**
     * Enable 2FA - Step 1: Generate Secret
     */
    public function enableTwoFactor(Request $request)
    {
        $google2fa = new Google2FA();
        $user = $request->user();

        // Generate a new secret if not already present
        if (!$user->two_factor_secret) {
            $user->two_factor_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'YGXone Ecosystem',
            $user->email,
            $user->two_factor_secret
        );

        return response()->json([
            'secret' => $user->two_factor_secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    /**
     * Verify 2FA - Step 2: Confirm OTP and Activate
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $google2fa = new Google2FA();
        $user = $request->user();

        $valid = $google2fa->verifyKey($user->two_factor_secret, $request->code);

        if ($valid) {
            $user->two_factor_status = true;
            $user->two_factor_verified = true;
            $user->save();

            ActivityLog::record($user->id, 'Activated Two-Factor Authentication', 'Security', '🛡️', $request->ip());

            return back()->with('status', 'two-factor-enabled');
        }

        return back()->withErrors(['code' => 'The provided security code is invalid.']);
    }

    /**
     * Disable 2FA
     */
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();
        $user->two_factor_status = false;
        $user->two_factor_verified = false;
        $user->two_factor_secret = null;
        $user->save();

        ActivityLog::record($user->id, 'Deactivated Two-Factor Authentication', 'Security', '🔓', $request->ip());

        return back()->with('status', 'two-factor-disabled');
    }

    /**
     * Display advanced social analytics (Social Growth)
     */
    public function analytics(Request $request)
    {
        $user = $request->user();
        
        // Calculate Global Rank
        $totalUsers = \App\Models\User::count();
        $rankPosition = \App\Models\User::where('stones', '>', $user->stones)->count() + 1;
        $rankPercentage = round(($rankPosition / $totalUsers) * 100, 1);

        // Social Metrics
        $totalPosts = \App\Models\Society\Post::where('user_id', $user->id)->count();
        $totalLikesReceived = \App\Models\Society\Like::whereHas('post', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        return view('profile.analytics', compact('user', 'totalUsers', 'rankPosition', 'rankPercentage', 'totalPosts', 'totalLikesReceived'));
    }

    /**
     * Display the user's activity log (My Activity)
     */
    public function activity(Request $request)
    {
        $activities = ActivityLog::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->created_at)->format('Y-m-d');
            });

        return view('profile.activity', [
            'activities' => $activities,
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        // Sync name to firstname and lastname for YG Pay compatibility
        $nameParts = explode(' ', $user->name, 2);
        $user->firstname = $nameParts[0] ?? '';
        $user->lastname = $nameParts[1] ?? '';

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle Avatar Upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->image) {
                \Storage::disk('public')->delete($user->image);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->image = $path;
        }

        $user->save();

        ActivityLog::record(
            $user->id,
            'Updated profile information',
            'Profile',
            '✏️',
            'YG Account'
        );

        // Broadcast profile change to all empire nodes
        app(CrossModuleSyncService::class)->syncProfileToAllNodes([
            'user_id' => $user->id,
            'fields' => [
                'name' => $user->name,
                'image' => $user->image,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
            ]
        ]);

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
