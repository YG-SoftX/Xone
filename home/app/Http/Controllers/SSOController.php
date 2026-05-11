<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SSOController extends Controller
{
    /**
     * Initiate SSO by redirecting to the YG Account service
     */
    public function initiate(Request $request)
    {
        $accountUrl = Config::get('services.yg_account.url', 'https://account.ygxone.com');
        $redirectUrl = $request->query('redirect', url()->previous() ?: route('search.home'));
        
        // Redirect to the account service with return URL
        return redirect($accountUrl . '/sso/initiate?redirect=' . urlencode($redirectUrl));
    }

    /**
     * Handle SSO callback from YG Account service
     */
    public function callback(Request $request)
    {
        // This would typically validate the SSO token and log the user in
        // For now, we'll just redirect to the home page
        return redirect()->route('search.home');
    }

    /**
     * Handle SSO logout
     */
    public function logout(Request $request)
    {
        $accountUrl = Config::get('services.yg_account.url', 'https://account.ygxone.com');
        $redirectUrl = $request->query('redirect', url('/'));
        
        return redirect($accountUrl . '/sso/logout?redirect=' . urlencode($redirectUrl));
    }
}