<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CredentialController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(int $projectId): View
    {
        $project     = $this->api->get("developer-console/projects/{$projectId}");
        $credentials = $this->api->get("developer-console/projects/{$projectId}/credentials");

        return view('credentials.index', [
            'project'     => $project,
            'credentials' => $credentials['data'] ?? $credentials,
        ]);
    }

    public function create(int $projectId): View
    {
        $project = $this->api->get("developer-console/projects/{$projectId}");
        return view('credentials.create', compact('project'));
    }

    public function store(Request $request, int $projectId): RedirectResponse
    {
        $data = $request->validate([
            'name'                            => 'required|string|max:100',
            'type'                            => 'required|in:api_key,oauth_client,service_account,webhook_secret',
            'scopes'                          => 'nullable|array',
            'restrictions.allowed_ips'        => 'nullable|string',
            'restrictions.allowed_referrers'  => 'nullable|string',
            'expires_at'                      => 'nullable|date|after:now',
        ]);

        $result = $this->api->post("developer-console/projects/{$projectId}/credentials", $data);

        if (isset($result['error'])) {
            return back()->withInput()->withErrors(['api' => $result['error']]);
        }

        // Flash the plaintext secret — shown only once
        session()->flash('new_credential_secret', $result['secret'] ?? $result['data']['secret'] ?? null);
        session()->flash('new_credential_id',     $result['id']     ?? $result['data']['id']     ?? null);

        return redirect()->route('projects.credentials', $projectId)
            ->with('success', 'Credential created. Copy the secret now — it will not be shown again.');
    }

    public function rotate(int $credentialId): RedirectResponse
    {
        $result = $this->api->post("developer-console/credentials/{$credentialId}/rotate");

        session()->flash('new_credential_secret', $result['secret'] ?? $result['data']['secret'] ?? null);

        return back()->with('success', 'Secret rotated. Copy the new secret now.');
    }

    public function toggle(int $credentialId): RedirectResponse
    {
        $this->api->post("developer-console/credentials/{$credentialId}/toggle");
        return back()->with('success', 'Credential status updated.');
    }

    public function destroy(int $credentialId): RedirectResponse
    {
        $this->api->delete("developer-console/credentials/{$credentialId}");
        return back()->with('success', 'Credential revoked.');
    }
}
