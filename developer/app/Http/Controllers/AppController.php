<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(): View
    {
        $apps = $this->api->get('developer/apps');
        return view('apps.index', ['apps' => $apps]);
    }

    public function create(): View
    {
        return view('apps.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'slug'         => 'required|string|max:50|alpha_dash',
            'description'  => 'nullable|string|max:500',
            'website_url'  => 'nullable|url|max:255',
            'redirect_uri' => 'required|url|max:500',
            'scopes'       => 'nullable|array',
        ]);

        $result = $this->api->post('developer/apps', $data);

        if (isset($result['error'])) {
            return back()->withInput()->withErrors(['api' => $result['error']]);
        }

        session()->flash('new_app_secret', $result['client_secret'] ?? null);

        return redirect()->route('apps.show', $result['id'] ?? $result['data']['id'])
            ->with('success', 'OAuth app created. Copy the client secret now.');
    }

    public function show(int $id): View
    {
        $app   = $this->api->get("developer/apps/{$id}");
        $stats = $this->api->get("developer/apps/{$id}/stats");
        return view('apps.show', compact('app', 'stats'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
            'website_url'  => 'nullable|url|max:255',
            'redirect_uri' => 'required|url|max:500',
            'scopes'       => 'nullable|array',
            'is_active'    => 'boolean',
        ]);

        $result = $this->api->put("developer/apps/{$id}", $data);

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'App updated.');
    }

    public function rotateSecret(int $id): RedirectResponse
    {
        $result = $this->api->post("developer/apps/{$id}/rotate-secret");

        session()->flash('new_app_secret', $result['client_secret'] ?? null);

        return back()->with('success', 'Client secret rotated. Copy the new secret now.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->api->delete("developer/apps/{$id}");
        return redirect()->route('apps.index')->with('success', 'App deleted.');
    }
}
