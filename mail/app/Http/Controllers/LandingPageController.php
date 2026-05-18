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

        // For demo, we just take the first setting or default
        $settings = MailSetting::first();
        
        if (!$settings || !$settings->show_landing_page) {
            return redirect('/admin');
        }

        return view('welcome', compact('settings'));
    }
}
