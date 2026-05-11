<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminAuthService $authService) {}

    public function showLogin(): \Illuminate\Contracts\View\View
    {
        return view('admin.login');
    }

    /**
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = $this->authService->attempt(
            $request->string('email')->lower()->value(),
            $request->input('password'),
            $request->ip()
        );

        session([
            'admin_user_id'       => $user->id,
            'admin_last_activity' => time(),
            'admin_login_time'    => time(),
            'admin_ip'            => $request->ip(),
            'admin_ua'            => $request->userAgent(),
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.dashboard');
    }

    public function logout(): RedirectResponse
    {
        session()->forget([
            'admin_user_id',
            'admin_last_activity',
            'admin_login_time',
            'admin_ip',
            'admin_ua',
        ]);

        return redirect()->route('admin.login');
    }
}
