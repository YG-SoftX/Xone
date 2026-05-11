<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IntegrationSettingsController extends Controller
{
    /**
     * Display integration settings page
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's API tokens (from Sanctum)
        $apiTokens = $user->tokens()->orderByDesc('created_at')->get();
        
        // Get webhook endpoints
        $webhooks = \DB::table('user_webhooks')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
        
        return view('settings.integrations.index', compact('user', 'apiTokens', 'webhooks'));
    }
    
    /**
     * Generate new API token
     */
    public function generateApiToken(Request $request)
    {
        $validated = $request->validate([
            'token_name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:today',
        ]);
        
        $user = Auth::user();
        
        // Create token with expiration if specified
        $token = $user->createToken(
            $validated['token_name'],
            ['*'], // All permissions
            $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null
        );
        
        return redirect()->back()->with('success', 'API token generated successfully!')
            ->with('new_token', $token->plainTextToken); // Show only once
    }
    
    /**
     * Revoke API token
     */
    public function revokeApiToken($tokenId)
    {
        $user = Auth::user();
        $token = $user->tokens()->findOrFail($tokenId);
        
        $token->delete();
        
        return redirect()->back()->with('success', 'API token revoked successfully!');
    }
    
    /**
     * Store webhook endpoint
     */
    public function storeWebhook(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'description' => 'nullable|string|max:500',
        ]);
        
        $user = Auth::user();
        
        // Generate HMAC secret for webhook signing
        $secret = Str::random(64);
        
        \DB::table('user_webhooks')->insert([
            'user_id' => $user->id,
            'url' => $validated['url'],
            'events' => json_encode($validated['events']),
            'secret' => $secret,
            'description' => $validated['description'],
            'is_active' => true,
            'success_count' => 0,
            'failure_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->back()->with('success', 'Webhook endpoint created successfully!');
    }
    
    /**
     * Toggle webhook active status
     */
    public function toggleWebhook($webhookId)
    {
        $user = Auth::user();
        $webhook = \DB::table('user_webhooks')
            ->where('id', $webhookId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        \DB::table('user_webhooks')
            ->where('id', $webhookId)
            ->update(['is_active' => !$webhook->is_active]);
        
        return redirect()->back()->with('success', 'Webhook status updated!');
    }
    
    /**
     * Delete webhook endpoint
     */
    public function deleteWebhook($webhookId)
    {
        $user = Auth::user();
        
        \DB::table('user_webhooks')
            ->where('id', $webhookId)
            ->where('user_id', $user->id)
            ->delete();
        
        return redirect()->back()->with('success', 'Webhook deleted successfully!');
    }
    
    /**
     * Test webhook delivery
     */
    public function testWebhook($webhookId)
    {
        $user = Auth::user();
        $webhook = \DB::table('user_webhooks')
            ->where('id', $webhookId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        // Dispatch test webhook job
        $testData = [
            'event' => 'webhook.test',
            'timestamp' => now()->toISOString(),
            'message' => 'This is a test webhook from YG Account',
            'user_id' => $user->id,
        ];
        
        \App\Jobs\DeliverWebhook::dispatch(
            (object) [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'secret' => $webhook->secret,
            ],
            $testData,
            'webhook.test'
        );
        
        return redirect()->back()->with('success', 'Test webhook dispatched! Check your endpoint.');
    }
}
