<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(Request $request, int $projectId): View
    {
        $period  = $request->get('period', '30d');
        $project = $this->api->get("developer-console/projects/{$projectId}");

        $overview    = $this->api->get("developer-console/projects/{$projectId}/analytics", ['period' => $period]);
        $errors      = $this->api->get("developer-console/projects/{$projectId}/analytics/errors", ['period' => $period]);
        $latency     = $this->api->get("developer-console/projects/{$projectId}/analytics/latency", ['period' => $period]);
        $endpoints   = $this->api->get("developer-console/projects/{$projectId}/analytics/endpoints", ['period' => $period, 'per_page' => 10]);
        $topCreds    = $this->api->get("developer-console/projects/{$projectId}/analytics/top-credentials", ['period' => $period]);

        return view('analytics.index', compact(
            'project', 'period', 'overview', 'errors', 'latency', 'endpoints', 'topCreds'
        ));
    }

    public function export(Request $request, int $projectId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $period = $request->get('period', '30d');

        $response = $this->api->raw('get', "developer-console/projects/{$projectId}/analytics/export", [
            'query' => ['period' => $period],
        ]);

        return response()->streamDownload(
            fn () => print($response->body()),
            "analytics-project-{$projectId}-{$period}.csv",
            ['Content-Type' => 'text/csv']
        );
    }
}
