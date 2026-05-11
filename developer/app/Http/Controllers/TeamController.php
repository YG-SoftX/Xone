<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(int $projectId): View
    {
        $project = $this->api->get("developer-console/projects/{$projectId}");
        $team    = $this->api->get("developer-console/projects/{$projectId}/team");

        return view('team.index', [
            'project' => $project,
            'members' => $team['data'] ?? $team,
        ]);
    }

    public function invite(Request $request, int $projectId): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:editor,viewer,billing_admin',
        ]);

        $result = $this->api->post("developer-console/projects/{$projectId}/team/invite", $data);

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', $result['message'] ?? 'Invitation sent.');
    }

    public function updateRole(Request $request, int $projectId, int $memberId): RedirectResponse
    {
        $data = $request->validate([
            'role' => 'required|in:editor,viewer,billing_admin',
        ]);

        $result = $this->api->put("developer-console/projects/{$projectId}/team/{$memberId}/role", $data);

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Role updated.');
    }

    public function remove(int $projectId, int $memberId): RedirectResponse
    {
        $result = $this->api->delete("developer-console/projects/{$projectId}/team/{$memberId}");

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Member removed.');
    }
}
