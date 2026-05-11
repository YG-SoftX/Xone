<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpVerificationController extends Controller
{
    /**
     * Show the OTP verification page
     */
    public function show(Request $request)
    {
        // If user already verified for this session, skip
        if ($request->session()->get('otp_verified_at')) {
            return redirect()->intended($request->action_url);
        }

        // Send OTP if not already sent recently
        if (!Auth::user()->ver_code) {
            OtpService::send(Auth::user(), 'high-security authorization');
        }

        return view('auth.otp-verify', [
            'action_url' => $request->action_url ?? route('dashboard')
        ]);
    }

    /**
     * Handle the OTP verification submission
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $user = Auth::user();

        if (OtpService::verify($user, $request->code)) {
            // Mark session as verified
            $request->session()->put('otp_verified_at', now());
            
            return redirect($request->action_url ?? route('dashboard'));
        }

        return back()->withErrors(['code' => 'The security code you entered is invalid or expired.']);
    }
}
