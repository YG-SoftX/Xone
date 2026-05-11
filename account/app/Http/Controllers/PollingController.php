<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use Illuminate\Http\Request;

class PollingController extends Controller
{
    protected $eventService;

    public function __construct(EventService $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Check for updates since last poll (AJAX polling for cPanel)
     */
    public function checkUpdates(Request $request)
    {
        $request->validate([
            'last_check' => 'required|date_format:U',
            'service' => 'nullable|string|in:mail,drive,docs,pay,meet',
        ]);

        $userId = auth()->id();
        $since = date('Y-m-d H:i:s', $request->input('last_check'));
        $service = $request->input('service');

        $events = $this->eventService->getUnprocessedEvents($userId, $since, $service);

        // Mark events as processed
        if ($events->isNotEmpty()) {
            $this->eventService->markAsProcessed($events->pluck('id')->toArray());
        }

        return response()->json([
            'has_updates' => $events->isNotEmpty(),
            'updates' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => $event->event_type,
                    'service' => $event->service,
                    'data' => json_decode($event->payload, true),
                    'timestamp' => $event->created_at->timestamp * 1000,
                ];
            }),
            'count' => $events->count(),
        ]);
    }

    /**
     * Register for push notifications (Web Push API)
     */
    public function registerPushToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:web,android,ios,desktop',
            'browser' => 'nullable|string',
            'device_info' => 'nullable|string',
        ]);

        \App\Models\PushNotificationToken::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'token' => $request->input('token'),
            ],
            [
                'platform' => $request->input('platform'),
                'browser' => $request->input('browser'),
                'device_info' => $request->input('device_info'),
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Unregister push notification token
     */
    public function unregisterPushToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        \App\Models\PushNotificationToken::where('user_id', auth()->id())
            ->where('token', $request->input('token'))
            ->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }
}
