<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use Illuminate\Http\Request;

class SsoController extends Controller
{
    protected SsoService $ssoService;

    public function __construct(SsoService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    /**
     * Redirect to YG Account for login
     */
    public function redirect()
    {
        return $this->ssoService->redirectToProvider();
    }

    /**
     * Handle callback from YG Account
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Authentication failed: ' . $request->input('error_description', 'Unknown error'),
            ]);
        }

        return $this->ssoService->handleProviderCallback();
    }

    /**
     * Logout and redirect to YG Account
     */
    public function logout(Request $request)
    {
        return $this->ssoService->logout();
    }
}
