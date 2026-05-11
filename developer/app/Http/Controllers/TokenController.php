<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TokenController extends Controller
{
    public function __construct(private readonly ApiClient $api) {}

    public function index(): View
    {
        $tokens = $this->api->get('tokens');
        return view('tokens.index', ['tokens' => $tokens]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'abilities'  => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $result = $this->api->post('tokens', $data);

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        session()->flash('new_token', $result['token'] ?? null);

        return back()->with('success', 'Token created. Copy it now — it will not be shown again.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->api->delete("tokens/{$id}");
        return back()->with('success', 'Token revoked.');
    }
}
