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

        $stats    = $this->fetch($apiBase . '/developer-console/dashboard', $user->api_token);
        $projects = $this->fetch($apiBase . '/developer-console/projects?per_page=5', $user->api_token);

        return view('dashboard', [
            'user'       => $user,
            'stats'      => $stats,
            'projects'   => $projects['data'] ?? [],
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
