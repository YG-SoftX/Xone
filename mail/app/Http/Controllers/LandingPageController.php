<?php

namespace App\Http\Controllers;

use App\Models\MailSetting;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect('/dashboard');
        }

        // Use settings from DB if available, otherwise use defaults
        $settings = MailSetting::first();
        
        // Always show landing page — if no DB settings, defaults are used in the view
        return view('welcome', compact('settings'));
    }
}
