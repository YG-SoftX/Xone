<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeveloperProject;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    /**
     * List webhooks for a project
     */
    public function index(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($projectId);

        $webhooks = Webhook::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'webhooks' => $webhooks->map(function($webhook) {
                return [
                    'id' => $webhook->id,
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'is_active' => $webhook->is_active,
                    'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                    'success_count' => $webhook->success_count,
                    'failure_count' => $webhook->failure_count,
                    'success_rate' => $webhook->getSuccessRate(),
                    'created_at' => $webhook->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $webhooks->currentPage(),
                'per_page' => $webhooks->perPage(),
                'total' => $webhooks->total(),
                'last_page' => $webhooks->lastPage(),
            ],
        ]);
    }

    /**
     * Show webhook details with delivery history
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $webhook = Webhook::whereHas('project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id);
              });
        })->findOrFail($id);

        // Get recent deliveries
        $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'webhook' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'success_count' => $webhook->success_count,
                'failure_count' => $webhook->failure_count,
                'success_rate' => $webhook->getSuccessRate(),
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'created_at' => $webhook->created_at->toIso8601String(),
            ],
            'recent_deliveries' => $deliveries->map(function($delivery) {
                return [
                    'id' => $delivery->id,
                    'event_type' => $delivery->event_type,
                    'status_code' => $delivery->status_code,
                    'success' => $delivery->success,
                    'attempt' => $delivery->attempt,
                    'next_retry_at' => $delivery->next_retry_at?->toIso8601String(),
                    'created_at' => $delivery->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Create new webhook
     */
    public function store(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $project = DeveloperProject::where(function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($projectId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'events' => 'required|array|min:1',
            'events.*' => 'string|max:100',
        ]);

        // Verify URL is accessible (optional health check)
        // In production, you might want to send a test ping here

        $webhook = Webhook::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => Str::random(64), // HMAC signing secret
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook created successfully',
            'webhook' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'secret' => $webhook->secret, // Show secret once
                'is_active' => $webhook->is_active,
            ],
            'warning' => 'Store this secret securely. Use it to verify webhook signatures.',
        ], 201);
    }

    /**
     * Toggle webhook active status
     */
    public function toggle(Request $request, $id)
    {
        $user = Auth::user();
        
        $webhook = Webhook::whereHas('project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($id);

        $newStatus = !$webhook->is_active;
        $webhook->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Webhook activated' : 'Webhook deactivated',
            'is_active' => $webhook->is_active,
        ]);
    }

    /**
     * Delete webhook
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $webhook = Webhook::whereHas('project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($id);

        $webhookName = $webhook->name;
        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => "Webhook '{$webhookName}' deleted successfully",
        ]);
    }

    /**
     * Redeliver failed webhook
     */
    public function redeliver($deliveryId)
    {
        $user = Auth::user();
        
        $delivery = WebhookDelivery::whereHas('webhook.project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($deliveryId);

        if ($delivery->success) {
            return response()->json(['error' => 'This delivery was already successful'], 400);
        }

        // Trigger redelivery job (in production, dispatch to queue)
        // For now, just reset the retry counter
        $delivery->update([
            'attempt' => 1,
            'next_retry_at' => now(),
            'status_code' => null,
            'response_body' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook redelivery queued',
            'delivery_id' => $delivery->id,
        ]);
    }

    /**
     * Test webhook (send sample payload)
     */
    public function test(Request $request, $id)
    {
        $user = Auth::user();
        
        $webhook = Webhook::whereHas('project', function($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', function($mq) use ($user) {
                  $mq->where('user_id', $user->id)
                     ->whereIn('role', ['owner', 'editor']);
              });
        })->findOrFail($id);

        // Generate test payload
        $testPayload = [
            'event' => 'test.webhook',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook from YG Developer Console',
                'project_id' => $webhook->project->project_id,
            ],
        ];

        $payloadJson = json_encode($testPayload);
        $signature = $webhook->generateSignature($payloadJson);

        // In production, dispatch to queue worker
        // For now, return the payload for manual testing
        return response()->json([
            'success' => true,
            'message' => 'Test webhook generated',
            'webhook_url' => $webhook->url,
            'payload' => $testPayload,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-YG-Signature' => 'sha256=' . $signature,
                'X-YG-Event' => 'test.webhook',
            ],
            'instructions' => 'Send this payload to your webhook URL to test your endpoint.',
        ]);
    }
}
