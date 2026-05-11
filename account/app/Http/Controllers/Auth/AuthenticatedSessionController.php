<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SsoController;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SecuritySentinelService;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
            'ssoService'       => session('sso_service'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Trigger Security Sentinel
        app(SecuritySentinelService::class)->alertNewLogin(auth()->user(), $request->ip());

        ActivityLog::record(
            auth()->id(),
            'Signed in to YG Account',
            'Sign in',
            '🔑',
            $request->ip()
        );

        // SSO: if a callback was set (from another YG app), redirect back with a token
        if ($callback = session('sso_callback')) {
            session()->forget(['sso_callback', 'sso_service']);
            return app(SsoController::class)->issueTokenAndRedirect(auth()->user(), $callback);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
