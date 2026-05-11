<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebhookController extends Controller
{
    // All valid event types supported by the YG Account webhook system
    private const EVENTS = [
        'billing'     => ['billing.invoice.created', 'billing.invoice.paid', 'billing.invoice.failed', 'billing.payment.received', 'billing.payment.failed'],
        'quota'       => ['quota.warning', 'quota.exceeded', 'quota.reset'],
        'credential'  => ['credential.created', 'credential.revoked', 'credential.expired', 'credential.rotated'],
        'project'     => ['project.created', 'project.deactivated', 'project.reactivated'],
        'team'        => ['team.member.invited', 'team.member.removed', 'team.member.role_changed'],
        'api'         => ['api.error.rate.high', 'api.latency.high'],
        'webhook'     => ['webhook.delivery.failed', 'webhook.delivery.recovered'],
    ];

    public function __construct(private readonly ApiClient $api) {}

    public function index(int $projectId): View
    {
        $project  = $this->api->get("developer-console/projects/{$projectId}");
        $webhooks = $this->api->get("developer-console/projects/{$projectId}/webhooks");

        return view('webhooks.index', [
            'project'  => $project,
            'webhooks' => $webhooks['data'] ?? $webhooks,
        ]);
    }

    public function create(int $projectId): View
    {
        $project = $this->api->get("developer-console/projects/{$projectId}");
        return view('webhooks.create', ['project' => $project, 'events' => self::EVENTS]);
    }

    public function store(Request $request, int $projectId): RedirectResponse
    {
        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'url'    => 'required|url|max:500',
            'events' => 'required|array|min:1',
        ]);

        $result = $this->api->post("developer-console/projects/{$projectId}/webhooks", $data);

        if (isset($result['error'])) {
            return back()->withInput()->withErrors(['api' => $result['error']]);
        }

        session()->flash('webhook_secret', $result['secret'] ?? $result['data']['secret'] ?? null);

        return redirect()->route('projects.webhooks', $projectId)
            ->with('success', 'Webhook created. Save the signing secret now — it will not be shown again.');
    }

    public function show(int $webhookId): View
    {
        $webhook    = $this->api->get("developer-console/webhooks/{$webhookId}");
        $deliveries = $this->api->get("developer-console/webhooks/{$webhookId}/deliveries");

        return view('webhooks.show', [
            'webhook'    => $webhook,
            'deliveries' => $deliveries['data'] ?? $deliveries,
        ]);
    }

    public function toggle(int $webhookId): RedirectResponse
    {
        $this->api->post("developer-console/webhooks/{$webhookId}/toggle");
        return back()->with('success', 'Webhook status updated.');
    }

    public function destroy(int $webhookId): RedirectResponse
    {
        $webhook = $this->api->get("developer-console/webhooks/{$webhookId}");
        $this->api->delete("developer-console/webhooks/{$webhookId}");

        $projectId = $webhook['project_id'] ?? null;
        return $projectId
            ? redirect()->route('projects.webhooks', $projectId)->with('success', 'Webhook deleted.')
            : redirect()->route('projects.index')->with('success', 'Webhook deleted.');
    }

    public function redeliver(int $deliveryId): RedirectResponse
    {
        $result = $this->api->post("developer-console/webhooks/deliveries/{$deliveryId}/redeliver");

        if (isset($result['error'])) {
            return back()->withErrors(['api' => $result['error']]);
        }

        return back()->with('success', 'Redelivery triggered.');
    }
}
