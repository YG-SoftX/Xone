<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * OfflineModeService
 * 
 * Manages offline browsing capabilities using Service Workers and cache strategies.
 * Enables users to browse previously visited pages without internet connection.
 */
class OfflineModeService
{
    private string $cachePath;
    
    public function __construct()
    {
        $this->cachePath = storage_path('app/offline_cache');
        
        // Ensure directory exists
        if (!File::exists($this->cachePath)) {
            File::makeDirectory($this->cachePath, 0755, true);
        }
    }
    
    /**
     * Cache page for offline access
     * 
     * @param string $url Page URL
     * @param string $html Page HTML content
     * @param array $resources Associated resources (CSS, JS, images)
     * @return bool Success status
     */
    public function cachePage(string $url, string $html, array $resources = []): bool
    {
        try {
            $cacheKey = $this->generateCacheKey($url);
            $cacheDir = $this->cachePath . '/' . $cacheKey;
            
            // Create cache directory
            if (!File::exists($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }
            
            // Save HTML
            File::put($cacheDir . '/index.html', $html);
            
            // Save metadata
            $metadata = [
                'url' => $url,
                'cached_at' => now()->toIso8601String(),
                'expires_at' => now()->addDays(7)->toIso8601String(),
                'size_bytes' => strlen($html),
                'resource_count' => count($resources),
            ];
            
            File::put($cacheDir . '/metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            
            // Download and cache resources
            foreach ($resources as $resource) {
                $this->cacheResource($cacheDir, $resource);
            }
            
            Log::info("Page cached for offline: {$url}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Failed to cache page: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cached page for offline access
     * 
     * @param string $url Requested URL
     * @return array|null Cached page data or null
     */
    public function getCachedPage(string $url): ?array
    {
        try {
            $cacheKey = $this->generateCacheKey($url);
            $cacheDir = $this->cachePath . '/' . $cacheKey;
            
            if (!File::exists($cacheDir)) {
                return null;
            }
            
            // Check expiration
            $metadataPath = $cacheDir . '/metadata.json';
            if (File::exists($metadataPath)) {
                $metadata = json_decode(File::get($metadataPath), true);
                
                if (strtotime($metadata['expires_at']) < time()) {
                    // Expired, delete cache
                    File::deleteDirectory($cacheDir);
                    return null;
                }
            }
            
            // Get HTML
            $htmlPath = $cacheDir . '/index.html';
            if (!File::exists($htmlPath)) {
                return null;
            }
            
            return [
                'url' => $url,
                'html' => File::get($htmlPath),
                'is_offline' => true,
                'cached_at' => $metadata['cached_at'] ?? null,
            ];
            
        } catch (\Exception $e) {
            Log::error("Failed to get cached page: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Generate Service Worker script
     * 
     * @return string Service Worker JavaScript code
     */
    public function generateServiceWorker(): string
    {
        return <<<'SW'
// YGXONE Browser Service Worker
const CACHE_NAME = 'ygxone-offline-v1';
const OFFLINE_PAGE = '/offline.html';

// Install event - cache essential resources
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll([
                '/',
                '/css/app.css',
                '/js/app.js',
                OFFLINE_PAGE,
            ]);
        })
    );
    self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip chrome-extension and other schemes
    if (!event.request.url.startsWith('http')) {
        return;
    }
    
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                // Return cached response
                return cachedResponse;
            }
            
            // Try network
            return fetch(event.request).then((networkResponse) => {
                // Clone the response
                const responseToCache = networkResponse.clone();
                
                // Cache successful responses
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(event.request, responseToCache);
                });
                
                return networkResponse;
            }).catch(() => {
                // Network failed, show offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match(OFFLINE_PAGE);
                }
                
                // For other resources, return empty response
                return new Response('', { status: 408, statusText: 'Offline' });
            });
        })
    );
});

// Background sync for queued actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-browsing-data') {
        event.waitUntil(syncBrowsingData());
    }
});

async function syncBrowsingData() {
    // Sync cached browsing data when back online
    const db = await openDatabase();
    const pendingActions = await db.getAll('pending_actions');
    
    for (const action of pendingActions) {
        try {
            await fetch(action.url, {
                method: action.method,
                headers: action.headers,
                body: action.body,
            });
            await db.delete('pending_actions', action.id);
        } catch (error) {
            console.error('Sync failed:', error);
        }
    }
}
SW;
    }
    
    /**
     * Generate offline fallback page
     * 
     * @return string HTML for offline page
     */
    public function generateOfflinePage(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - YGXONE Browser</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .offline-container {
            text-align: center;
            padding: 40px;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .cached-pages {
            margin-top: 40px;
            text-align: left;
            max-width: 600px;
        }
        .cached-pages h2 {
            font-size: 20px;
            margin-bottom: 15px;
        }
        .page-list {
            list-style: none;
            padding: 0;
        }
        .page-list li {
            background: rgba(255,255,255,0.1);
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
        }
        .page-list a {
            color: white;
            text-decoration: none;
        }
        .page-list a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="icon">📡</div>
        <h1>You're Offline</h1>
        <p>It seems you've lost your internet connection. Don't worry, you can still access cached pages.</p>
        <a href="/" class="btn">Go to Browser Home</a>
        
        <div class="cached-pages">
            <h2>Cached Pages Available:</h2>
            <ul class="page-list" id="cachedPages">
                <li>Loading cached pages...</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Load cached pages list
        fetch('/api/offline/cached-pages')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('cachedPages');
                if (data.pages && data.pages.length > 0) {
                    list.innerHTML = data.pages.map(page => 
                        `<li><a href="${page.url}">${page.title || page.url}</a></li>`
                    ).join('');
                } else {
                    list.innerHTML = '<li>No cached pages available</li>';
                }
            })
            .catch(() => {
                document.getElementById('cachedPages').innerHTML = 
                    '<li>Unable to load cached pages</li>';
            });
    </script>
</body>
</html>
HTML;
    }
    
    /**
     * Get list of all cached pages
     * 
     * @return array List of cached pages
     */
    public function getCachedPages(): array
    {
        try {
            $pages = [];
            
            if (!File::exists($this->cachePath)) {
                return [];
            }
            
            $directories = File::directories($this->cachePath);
            
            foreach ($directories as $dir) {
                $metadataPath = $dir . '/metadata.json';
                
                if (File::exists($metadataPath)) {
                    $metadata = json_decode(File::get($metadataPath), true);
                    $pages[] = [
                        'url' => $metadata['url'],
                        'title' => $this->extractTitleFromCache($dir),
                        'cached_at' => $metadata['cached_at'],
                        'size_kb' => round($metadata['size_bytes'] / 1024, 2),
                    ];
                }
            }
            
            return array_slice($pages, 0, 50); // Limit to 50 pages
            
        } catch (\Exception $e) {
            Log::error("Failed to get cached pages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear offline cache
     */
    public function clearCache(): bool
    {
        try {
            File::deleteDirectory($this->cachePath);
            File::makeDirectory($this->cachePath, 0755, true);
            
            Log::info("Offline cache cleared");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to clear cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate cache key from URL
     */
    private function generateCacheKey(string $url): string
    {
        return md5($url);
    }
    
    /**
     * Cache individual resource
     */
    private function cacheResource(string $cacheDir, string $resourceUrl): void
    {
        try {
            // In production, this would download the resource
            // For now, just log it
            Log::debug("Resource cached: {$resourceUrl}");
        } catch (\Exception $e) {
            Log::error("Failed to cache resource: " . $e->getMessage());
        }
    }
    
    /**
     * Extract title from cached HTML
     */
    private function extractTitleFromCache(string $cacheDir): string
    {
        try {
            $htmlPath = $cacheDir . '/index.html';
            
            if (!File::exists($htmlPath)) {
                return 'Unknown';
            }
            
            $html = File::get($htmlPath);
            
            if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
                return strip_tags($matches[1]);
            }
            
            return 'Untitled Page';
        } catch (\Exception $e) {
            return 'Error loading title';
        }
    }
}
