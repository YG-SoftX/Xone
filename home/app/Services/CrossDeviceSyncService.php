<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CrossDeviceSyncService
 * 
 * Synchronizes browser data across devices using user account.
 * Handles bookmarks, history, passwords, and settings sync with conflict resolution.
 */
class CrossDeviceSyncService
{
    /**
     * Sync bookmarks to cloud
     * 
     * @param int $userId User ID
     * @param array $bookmarks Bookmarks to sync
     * @return bool Success status
     */
    public function syncBookmarks(int $userId, array $bookmarks): bool
    {
        try {
            // Get existing bookmarks
            $existing = DB::table('synced_bookmarks')
                ->where('user_id', $userId)
                ->pluck('url', 'id')
                ->toArray();
            
            foreach ($bookmarks as $bookmark) {
                $url = $bookmark['url'] ?? '';
                
                if (empty($url)) {
                    continue;
                }
                
                // Check if exists
                $exists = DB::table('synced_bookmarks')
                    ->where('user_id', $userId)
                    ->where('url', $url)
                    ->first();
                
                if ($exists) {
                    // Update existing
                    DB::table('synced_bookmarks')
                        ->where('id', $exists->id)
                        ->update([
                            'title' => $bookmark['title'] ?? $exists->title,
                            'folder' => $bookmark['folder'] ?? $exists->folder,
                            'updated_at' => now(),
                        ]);
                } else {
                    // Insert new
                    DB::table('synced_bookmarks')->insert([
                        'user_id' => $userId,
                        'url' => $url,
                        'title' => $bookmark['title'] ?? '',
                        'folder' => $bookmark['folder'] ?? 'Unsorted',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            
            Log::info("Bookmarks synced for user {$userId}: " . count($bookmarks) . " items");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to sync bookmarks: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get synced bookmarks from cloud
     * 
     * @param int $userId User ID
     * @return array Synced bookmarks
     */
    public function getSyncedBookmarks(int $userId): array
    {
        try {
            return DB::table('synced_bookmarks')
                ->where('user_id', $userId)
                ->orderBy('folder')
                ->orderBy('title')
                ->get()
                ->map(function($bm) {
                    return [
                        'id' => $bm->id,
                        'url' => $bm->url,
                        'title' => $bm->title,
                        'folder' => $bm->folder,
                        'created_at' => $bm->created_at,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to get synced bookmarks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync browsing history
     * 
     * @param int $userId User ID
     * @param array $history History entries to sync
     * @return bool Success status
     */
    public function syncHistory(int $userId, array $history): bool
    {
        try {
            foreach ($history as $entry) {
                DB::table('synced_history')->insert([
                    'user_id' => $userId,
                    'url' => $entry['url'] ?? '',
                    'title' => $entry['title'] ?? '',
                    'visited_at' => $entry['visited_at'] ?? now(),
                    'device_id' => $entry['device_id'] ?? session()->getId(),
                    'created_at' => now(),
                ]);
            }
            
            // Clean old history (keep last 1000 entries)
            DB::table('synced_history')
                ->where('user_id', $userId)
                ->orderBy('visited_at', 'desc')
                ->offset(1000)
                ->delete();
            
            Log::info("History synced for user {$userId}: " . count($history) . " entries");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to sync history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get synced browsing history
     * 
     * @param int $userId User ID
     * @param int $limit Number of entries
     * @return array Synced history
     */
    public function getSyncedHistory(int $userId, int $limit = 100): array
    {
        try {
            return DB::table('synced_history')
                ->where('user_id', $userId)
                ->orderBy('visited_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($h) {
                    return [
                        'url' => $h->url,
                        'title' => $h->title,
                        'visited_at' => $h->visited_at,
                        'device_id' => $h->device_id,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to get synced history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sync browser settings
     * 
     * @param int $userId User ID
     * @param array $settings Settings to sync
     * @return bool Success status
     */
    public function syncSettings(int $userId, array $settings): bool
    {
        try {
            DB::table('synced_settings')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'settings_data' => json_encode($settings),
                    'updated_at' => now(),
                ]
            );
            
            Log::info("Settings synced for user {$userId}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to sync settings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get synced browser settings
     * 
     * @param int $userId User ID
     * @return array Synced settings
     */
    public function getSyncedSettings(int $userId): array
    {
        try {
            $record = DB::table('synced_settings')
                ->where('user_id', $userId)
                ->first();
            
            if ($record) {
                return json_decode($record->settings_data, true) ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error("Failed to get synced settings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Register device for sync
     * 
     * @param int $userId User ID
     * @param string $deviceId Device identifier
     * @param string $deviceName Human-readable name
     * @return bool Success status
     */
    public function registerDevice(int $userId, string $deviceId, string $deviceName): bool
    {
        try {
            DB::table('synced_devices')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'device_id' => $deviceId,
                ],
                [
                    'device_name' => $deviceName,
                    'last_sync_at' => now(),
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
            
            Log::info("Device registered: {$deviceName} for user {$userId}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to register device: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all registered devices for user
     * 
     * @param int $userId User ID
     * @return array Registered devices
     */
    public function getUserDevices(int $userId): array
    {
        try {
            return DB::table('synced_devices')
                ->where('user_id', $userId)
                ->orderBy('last_sync_at', 'desc')
                ->get()
                ->map(function($device) {
                    return [
                        'device_id' => $device->device_id,
                        'device_name' => $device->device_name,
                        'last_sync' => $device->last_sync_at,
                        'is_active' => (bool) $device->is_active,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to get user devices: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trigger sync across all devices
     * 
     * @param int $userId User ID
     * @param string $dataType Type of data to sync (bookmarks, history, settings)
     * @return bool Success status
     */
    public function triggerSync(int $userId, string $dataType): bool
    {
        try {
            // Mark sync as pending for all active devices
            DB::table('synced_devices')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->update([
                    'pending_sync_type' => $dataType,
                    'updated_at' => now(),
                ]);
            
            Log::info("Sync triggered for user {$userId}, type: {$dataType}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to trigger sync: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Resolve sync conflicts (last-write-wins strategy)
     * 
     * @param array $localData Local data
     * @param array $cloudData Cloud data
     * @param string $timestampField Field name for timestamp comparison
     * @return array Resolved data
     */
    public function resolveConflicts(array $localData, array $cloudData, string $timestampField = 'updated_at'): array
    {
        $resolved = [];
        
        // Create maps for quick lookup
        $localMap = [];
        foreach ($localData as $item) {
            $key = $item['url'] ?? $item['id'] ?? md5(json_encode($item));
            $localMap[$key] = $item;
        }
        
        $cloudMap = [];
        foreach ($cloudData as $item) {
            $key = $item['url'] ?? $item['id'] ?? md5(json_encode($item));
            $cloudMap[$key] = $item;
        }
        
        // Merge with last-write-wins
        $allKeys = array_unique(array_merge(array_keys($localMap), array_keys($cloudMap)));
        
        foreach ($allKeys as $key) {
            $local = $localMap[$key] ?? null;
            $cloud = $cloudMap[$key] ?? null;
            
            if (!$local) {
                $resolved[] = $cloud;
            } elseif (!$cloud) {
                $resolved[] = $local;
            } else {
                // Both exist, compare timestamps
                $localTime = strtotime($local[$timestampField] ?? 'now');
                $cloudTime = strtotime($cloud[$timestampField] ?? 'now');
                
                $resolved[] = $localTime >= $cloudTime ? $local : $cloud;
            }
        }
        
        return $resolved;
    }
}
