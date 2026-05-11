<?php

namespace App\Http\Controllers;

use App\Services\UnifiedNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(UnifiedNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $limit = $request->input('limit', 20);
        $filter = $request->input('filter', 'all'); // all, mail, calendar, drive, etc.
        
        try {
            $result = $this->notificationService->getNotifications($userId, $limit, $filter);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load notifications',
            ], 500);
        }
    }

    /**
     * Get notification summary for dashboard widget
     */
    public function summary(): JsonResponse
    {
        $userId = auth()->id();
        
        try {
            $summary = $this->notificationService->getSummary($userId);
            
            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load summary',
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): JsonResponse
    {
        $userId = auth()->id();
        
        try {
            $success = $this->notificationService->markAsRead($userId, $notificationId);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification',
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $service = $request->input('service'); // Optional: mark all for specific service
        
        try {
            $count = $this->notificationService->markAllAsRead($userId, $service);
            
            return response()->json([
                'success' => true,
                'message' => "Marked {$count} notifications as read",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(int $notificationId): JsonResponse
    {
        $userId = auth()->id();
        
        try {
            $success = $this->notificationService->deleteNotification($userId, $notificationId);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification deleted',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
            ], 500);
        }
    }

    /**
     * Create notification (internal API for services)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'service' => 'required|string|in:mail,calendar,drive,docs,xcel,chat,contacts,pay,forms,meet',
            'type' => 'required|string',
            'title' => 'required|string|max:200',
            'message' => 'nullable|string|max:1000',
            'action_url' => 'nullable|string|url',
            'icon' => 'nullable|string',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'metadata' => 'nullable|array',
        ]);
        
        try {
            $this->notificationService->createNotification(
                $request->input('user_id'),
                $request->input('service'),
                $request->input('type'),
                [
                    'title' => $request->input('title'),
                    'message' => $request->input('message', ''),
                    'action_url' => $request->input('action_url'),
                    'icon' => $request->input('icon'),
                    'priority' => $request->input('priority', 'normal'),
                    'metadata' => $request->input('metadata', []),
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Notification created',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification',
            ], 500);
        }
    }
}
