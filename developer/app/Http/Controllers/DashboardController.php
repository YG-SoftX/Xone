<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Developer console overview — pulls summary data from the YG Account API.
     */
    public function index(): View
    {
        $user    = auth()->user();
        $apiBase = rtrim(config('services.yg_account.api_base'), '/');

        // If the user's SSO token hasn't been populated yet, default to empty data
        // rather than failing silently.
        if (empty($user->api_token)) {
            return view('dashboard', [
                'user'       => $user,
                'stats'      => [],
                'projects'   => [],
                'accountUrl' => config('services.yg_account.url'),
            ]);
        }

        $dashboardData = $this->fetch($apiBase . '/developer-console/dashboard', $user->api_token);
        
        $stats    = $dashboardData['stats'] ?? [];
        $projects = $dashboardData['recent_projects'] ?? [];

        return view('dashboard', [
            'user'       => $user,
            'stats'      => $stats,
            'projects'   => $projects,
            'accountUrl' => config('services.yg_account.url'),
        ]);
    }

    /**
     * Make an authenticated GET request to the YG Account API.
     * Returns the decoded JSON body, or [] on any failure.
     */
    private function fetch(string $url, string $token): array
    {
        try {
            $response = Http::withToken($token)->timeout(8)->get($url);
            return $response->ok() ? $response->json() ?? [] : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
