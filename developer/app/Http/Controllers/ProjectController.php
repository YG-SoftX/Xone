<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(): View
    {
        $projects = $this->api->get('developer-console/projects');
        return view('projects.index', ['projects' => $projects['data'] ?? $projects]);
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'website_url' => 'nullable|url|max:255',
            'environment' => 'required|in:development,staging,production',
        ]);

        $result = $this->api->post('developer-console/projects', $data);

        if (isset($result['error'])) {
            return back()->withInput()->withErrors(['api' => $result['error']]);
        }

        $projectId = $result['project']['id'] ?? $result['id'] ?? $result['data']['id'] ?? null;

        return redirect()->route('projects.show', $projectId)
            ->with('success', 'Project created successfully.');
    }

    public function show(int $id): View
    {
        $response = $this->api->get("developer-console/projects/{$id}");
        
        $project     = $response['project'] ?? $response;
        $credentials = $project['credentials'] ?? [];
        $quotas      = $project['quotas'] ?? [];
        $team        = $project['members'] ?? [];
        
        $webhooksResponse = $this->api->get("developer-console/projects/{$id}/webhooks");
        $webhooks = $webhooksResponse['data'] ?? $webhooksResponse;

        return view('projects.show', [
            'project'     => $project,
            'credentials' => $credentials,
            'quotas'      => $quotas,
            'team'        => $team,
            'webhooks'    => $webhooks,
        ]);
    }

    public function edit(int $id): View
    {
        $response = $this->api->get("developer-console/projects/{$id}");
        $project  = $response['project'] ?? $response;
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'website_url' => 'nullable|url|max:255',
            'environment' => 'required|in:development,staging,production',
        ]);

        $result = $this->api->put("developer-console/projects/{$id}", $data);

        if (isset($result['error'])) {
            return back()->withInput()->withErrors(['api' => $result['error']]);
        }

        return redirect()->route('projects.show', $id)->with('success', 'Project updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->api->delete("developer-console/projects/{$id}");
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    public function activate(int $id): RedirectResponse
    {
        $this->api->post("developer-console/projects/{$id}/activate");
        return back()->with('success', 'Project reactivated.');
    }
}
