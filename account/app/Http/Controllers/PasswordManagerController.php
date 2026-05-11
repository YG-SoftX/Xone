<?php

namespace App\Http\Controllers;

use App\Models\PasswordCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasswordManagerController extends Controller
{
    /**
     * Display the password manager dashboard.
     */
    public function index()
    {
        $credentials = Auth::user()->passwords()
            ->orderBy('site_name')
            ->get();

        return view('passwords.index', compact('credentials'));
    }

    /**
     * Store a new credential.
     */
    public function store(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_url' => 'nullable|url|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'category' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        Auth::user()->passwords()->create([
            'site_name' => $request->site_name,
            'site_url' => $request->site_url,
            'username' => $request->username,
            'encrypted_password' => encrypt($request->password), // Sovereign Encryption
            'category' => $request->category,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Password saved to your YG Vault.');
    }

    /**
     * Update a credential.
     */
    public function update(Request $request, PasswordCredential $password)
    {
        $this->authorize('update', $password);

        $request->validate([
            'site_name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
        ]);

        $data = [
            'site_name' => $request->site_name,
            'username' => $request->username,
        ];

        if ($request->filled('password')) {
            $data['encrypted_password'] = encrypt($request->password);
        }

        $password->update($data);

        return redirect()->back()->with('success', 'Credential updated.');
    }

    /**
     * Delete a credential.
     */
    public function destroy(PasswordCredential $password)
    {
        $this->authorize('delete', $password);
        $password->delete();

        return redirect()->back()->with('success', 'Credential removed from Vault.');
    }
}
