<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedNotificationService
{
    /**
     * Create a notification from any service
     */
    public function createNotification(int $userId, string $service, string $type, array $data): void
    {
        try {
            DB::table('notifications')->insert([
                'user_id' => $userId,
                'service' => $service,
                'type' => $type,
                'title' => $data['title'] ?? '',
                'message' => $data['message'] ?? '',
                'action_url' => $data['action_url'] ?? null,
                'icon' => $data['icon'] ?? $this->getServiceIcon($service),
                'priority' => $data['priority'] ?? 'normal', // low, normal, high, urgent
                'is_read' => false,
                'metadata' => json_encode($data['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            Log::info("Notification created", [
                'user_id' => $userId,
                'service' => $service,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create notification", [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'service' => $service,
            ]);
        }
    }

    /**
     * Get notifications for user with pagination
     */
    public function getNotifications(int $userId, int $limit = 20, ?string $filter = null): array
    {
        try {
            $query = DB::table('notifications')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc');
            
            // Apply filter if specified
            if ($filter && $filter !== 'all') {
                $query->where('service', $filter);
            }
            
            $notifications = $query->limit($limit)->get()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'service' => $notification->service,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'action_url' => $notification->action_url,
                    'icon' => $notification->icon,
                    'priority' => $notification->priority,
                    'is_read' => (bool) $notification->is_read,
                    'created_at' => $notification->created_at,
                    'time_ago' => $this->getTimeAgo($notification->created_at),
                ];
            })->toArray();
            
            // Get unread count
            $unreadCount = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', false)
                ->count();
            
            return [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'has_more' => count($notifications) >= $limit,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get notifications", [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            
            return [
                'notifications' => [],
                'unread_count' => 0,
                'has_more' => false,
            ];
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $userId, int $notificationId): bool
    {
        try {
            $updated = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('id', $notificationId)
                ->update([
                    'is_read' => true,
                    'updated_at' => now(),
                ]);
            
            return $updated > 0;
        } catch (\Exception $e) {
            Log::error("Failed to mark notification as read", [
                'error' => $e->getMessage(),
                'notification_id' => $notificationId,
            ]);
            
            return false;
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId, ?string $service = null): int
    {
        try {
            $query = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', false);
            
            if ($service) {
                $query->where('service', $service);
            }
            
            return $query->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to mark all notifications as read", [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            
            return 0;
        }
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $userId, int $notificationId): bool
    {
        try {
            $deleted = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('id', $notificationId)
                ->delete();
            
            return $deleted > 0;
        } catch (\Exception $e) {
            Log::error("Failed to delete notification", [
                'error' => $e->getMessage(),
                'notification_id' => $notificationId,
            ]);
            
            return false;
        }
    }

    /**
     * Get notification summary for dashboard widget
     */
    public function getSummary(int $userId): array
    {
        try {
            // Unread count by service
            $byService = DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', false)
                ->select('service', DB::raw('count(*) as count'))
                ->groupBy('service')
                ->get()
                ->pluck('count', 'service')
                ->toArray();
            
            // Recent notifications (last 5)
            $recent = DB::table('notifications')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'title' => $n->title,
                        'service' => $n->service,
                        'is_read' => (bool) $n->is_read,
                        'time_ago' => $this->getTimeAgo($n->created_at),
                    ];
                })
                ->toArray();
            
            return [
                'total_unread' => array_sum($byService),
                'by_service' => $byService,
                'recent' => $recent,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get notification summary", [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_unread' => 0,
                'by_service' => [],
                'recent' => [],
            ];
        }
    }

    /**
     * Clean old notifications (older than 30 days)
     */
    public function cleanupOldNotifications(): int
    {
        try {
            return DB::table('notifications')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();
        } catch (\Exception $e) {
            Log::error("Failed to cleanup old notifications", [
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }

    // ========== PRIVATE HELPER METHODS ==========

    /**
     * Get icon class for service
     */
    protected function getServiceIcon(string $service): string
    {
        $icons = [
            'mail' => 'envelope',
            'calendar' => 'calendar-alt',
            'drive' => 'folder',
            'docs' => 'file-alt',
            'xcel' => 'file-excel',
            'chat' => 'comments',
            'contacts' => 'address-book',
            'pay' => 'credit-card',
            'forms' => 'clipboard-list',
            'meet' => 'video',
        ];
        
        return $icons[$service] ?? 'bell';
    }

    /**
     * Format time ago string
     */
    protected function getTimeAgo(string $datetime): string
    {
        $now = new \DateTime();
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->d > 7) {
            return $ago->format('M j, Y');
        } elseif ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }
}
