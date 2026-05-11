<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotaController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(int $projectId): View
    {
        $project = $this->api->get("developer-console/projects/{$projectId}");
        $quotas  = $this->api->get("developer-console/projects/{$projectId}/quotas");
        $alerts  = $this->api->get("developer-console/projects/{$projectId}/quotas/alerts");
        $usage   = $this->api->get("developer-console/projects/{$projectId}/quotas/usage");

        return view('quotas.index', [
            'project' => $project,
            'quotas'  => $quotas['data'] ?? $quotas,
            'alerts'  => $alerts['active_alerts'] ?? [],
            'usage'   => $usage['usage'] ?? [],
        ]);
    }

    public function update(Request $request, int $projectId, int $productId): RedirectResponse
    {
        $data = $request->validate([
            'daily_limit'           => 'nullable|integer|min:0',
            'monthly_limit'         => 'nullable|integer|min:0',
            'rate_limit_per_minute' => 'nullable|integer|min:0',
        ]);

        $result = $this->api->put(
            "developer-console/projects/{$projectId}/quotas/{$productId}",
            array_filter($data, fn ($v) => $v !== null)
        );

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Quota updated.');
    }
}
