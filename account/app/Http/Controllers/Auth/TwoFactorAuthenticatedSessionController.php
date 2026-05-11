<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PragmaRX\Google2FALaravel\Google2FA;

class TwoFactorAuthenticatedSessionController extends Controller
{
    /**
     * Show the 2FA verification form.
     */
    public function create()
    {
        if (! Session::has('login_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Verify the 2FA code.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = Session::get('login_user_id');
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'code' => 'Session expired. Please log in again.',
            ]);
        }

        $google2fa = new Google2FA();

        // Validate the 2FA code
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->code);

        if (! $valid) {
            // Check recovery codes
            if ($user->recovery_codes) {
                $recoveryCodes = json_decode(decrypt($user->recovery_codes), true);
                $codeIndex = array_search($request->code, $recoveryCodes);

                if ($codeIndex !== false) {
                    // Remove used recovery code
                    unset($recoveryCodes[$codeIndex]);
                    $user->recovery_codes = encrypt(array_values($recoveryCodes));
                    $user->save();
                } else {
                    throw ValidationException::withMessages([
                        'code' => 'Invalid verification or recovery code.',
                    ]);
                }
            } else {
                throw ValidationException::withMessages([
                    'code' => 'Invalid verification code.',
                ]);
            }
        }

        // Log the user in
        Auth::login($user, $request->boolean('remember'));
        Session::forget('login_user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
