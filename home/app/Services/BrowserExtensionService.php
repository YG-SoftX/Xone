<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

/**
 * BrowserExtensionService
 * 
 * Manages browser extensions with marketplace, installation, and execution.
 * Provides extension SDK for developers to create custom extensions.
 */
class BrowserExtensionService
{
    private string $extensionsPath;
    
    public function __construct()
    {
        $this->extensionsPath = storage_path('app/browser_extensions');
        
        // Ensure directory exists
        if (!File::exists($this->extensionsPath)) {
            File::makeDirectory($this->extensionsPath, 0755, true);
        }
    }
    
    /**
     * Install extension from uploaded package
     * 
     * @param array $extensionData Extension metadata
     * @param string $zipPath Path to extension ZIP file
     * @return bool Success status
     */
    public function installExtension(array $extensionData, string $zipPath): bool
    {
        try {
            $extensionId = $extensionData['id'] ?? uniqid('ext_');
            $extensionDir = $this->extensionsPath . '/' . $extensionId;
            
            // Create extension directory
            if (!File::exists($extensionDir)) {
                File::makeDirectory($extensionDir, 0755, true);
            }
            
            // Extract ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($extensionDir);
                $zip->close();
            } else {
                throw new \Exception("Failed to extract extension ZIP");
            }
            
            // Validate manifest.json
            $manifestPath = $extensionDir . '/manifest.json';
            if (!File::exists($manifestPath)) {
                throw new \Exception("Missing manifest.json");
            }
            
            $manifest = json_decode(File::get($manifestPath), true);
            
            // Store in database
            DB::table('browser_extensions')->updateOrInsert(
                ['extension_id' => $extensionId],
                [
                    'name' => $manifest['name'] ?? $extensionData['name'],
                    'version' => $manifest['version'] ?? '1.0.0',
                    'description' => $manifest['description'] ?? '',
                    'author' => $manifest['author'] ?? 'Unknown',
                    'permissions' => json_encode($manifest['permissions'] ?? []),
                    'content_scripts' => json_encode($manifest['content_scripts'] ?? []),
                    'background_scripts' => json_encode($manifest['background_scripts'] ?? []),
                    'is_enabled' => true,
                    'installed_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            Log::info("Extension installed: {$extensionId}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Extension installation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all installed extensions
     * 
     * @return array List of extensions
     */
    public function getInstalledExtensions(): array
    {
        try {
            return DB::table('browser_extensions')
                ->orderBy('installed_at', 'desc')
                ->get()
                ->map(function($ext) {
                    return [
                        'id' => $ext->extension_id,
                        'name' => $ext->name,
                        'version' => $ext->version,
                        'description' => $ext->description,
                        'author' => $ext->author,
                        'enabled' => (bool) $ext->is_enabled,
                        'permissions' => json_decode($ext->permissions, true),
                        'installed_at' => $ext->installed_at,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to get extensions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Enable/disable extension
     */
    public function toggleExtension(string $extensionId, bool $enabled): bool
    {
        try {
            DB::table('browser_extensions')
                ->where('extension_id', $extensionId)
                ->update([
                    'is_enabled' => $enabled,
                    'updated_at' => now(),
                ]);
            
            Log::info("Extension " . ($enabled ? 'enabled' : 'disabled') . ": {$extensionId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to toggle extension: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Uninstall extension
     */
    public function uninstallExtension(string $extensionId): bool
    {
        try {
            // Delete files
            $extensionDir = $this->extensionsPath . '/' . $extensionId;
            if (File::exists($extensionDir)) {
                File::deleteDirectory($extensionDir);
            }
            
            // Remove from database
            DB::table('browser_extensions')
                ->where('extension_id', $extensionId)
                ->delete();
            
            Log::info("Extension uninstalled: {$extensionId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to uninstall extension: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get extension content scripts for URL
     * 
     * @param string $url Target URL
     * @return array Scripts to inject
     */
    public function getContentScriptsForUrl(string $url): array
    {
        try {
            $enabledExtensions = DB::table('browser_extensions')
                ->where('is_enabled', true)
                ->get();
            
            $scripts = [];
            
            foreach ($enabledExtensions as $ext) {
                $contentScripts = json_decode($ext->content_scripts, true);
                
                foreach ($contentScripts as $script) {
                    // Check if URL matches
                    if ($this->urlMatchesPatterns($url, $script['matches'] ?? [])) {
                        $scripts[] = [
                            'extension_id' => $ext->extension_id,
                            'js' => $script['js'] ?? [],
                            'css' => $script['css'] ?? [],
                            'run_at' => $script['run_at'] ?? 'document_idle',
                        ];
                    }
                }
            }
            
            return $scripts;
        } catch (\Exception $e) {
            Log::error("Failed to get content scripts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if URL matches extension patterns
     */
    private function urlMatchesPatterns(string $url, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // Convert wildcard pattern to regex
            $regex = str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/'));
            $regex = '/^' . $regex . '$/i';
            
            if (preg_match($regex, $url)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get available extensions from marketplace (mock data for now)
     */
    public function getMarketplaceExtensions(): array
    {
        return [
            [
                'id' => 'adblock-plus',
                'name' => 'AdBlock Plus',
                'version' => '3.15.2',
                'description' => 'Block ads and trackers across the web',
                'author' => 'YG Team',
                'rating' => 4.8,
                'installs' => 125000,
                'category' => 'Privacy',
                'icon' => 'fas fa-shield-alt',
            ],
            [
                'id' => 'dark-reader',
                'name' => 'Dark Reader',
                'version' => '4.9.58',
                'description' => 'Force dark mode on all websites',
                'author' => 'YG Team',
                'rating' => 4.9,
                'installs' => 98000,
                'category' => 'Appearance',
                'icon' => 'fas fa-moon',
            ],
            [
                'id' => 'screenshot-tool',
                'name' => 'Screenshot Tool',
                'version' => '2.1.0',
                'description' => 'Capture full-page or visible area screenshots',
                'author' => 'YG Team',
                'rating' => 4.7,
                'installs' => 67000,
                'category' => 'Productivity',
                'icon' => 'fas fa-camera',
            ],
            [
                'id' => 'price-tracker',
                'name' => 'Price Tracker',
                'version' => '1.5.3',
                'description' => 'Monitor product prices and get alerts on drops',
                'author' => 'YG Team',
                'rating' => 4.6,
                'installs' => 45000,
                'category' => 'Shopping',
                'icon' => 'fas fa-tags',
            ],
            [
                'id' => 'reading-time',
                'name' => 'Reading Time Estimator',
                'version' => '1.2.0',
                'description' => 'Show estimated reading time for articles',
                'author' => 'YG Team',
                'rating' => 4.5,
                'installs' => 32000,
                'category' => 'Productivity',
                'icon' => 'fas fa-clock',
            ],
        ];
    }
    
    /**
     * Execute extension background script
     */
    public function executeBackgroundScript(string $extensionId, string $scriptName, array $data = []): mixed
    {
        try {
            $extensionDir = $this->extensionsPath . '/' . $extensionId;
            $scriptPath = $extensionDir . '/background/' . $scriptName . '.php';
            
            if (!File::exists($scriptPath)) {
                throw new \Exception("Script not found: {$scriptName}");
            }
            
            // Include and execute script
            return include $scriptPath;
            
        } catch (\Exception $e) {
            Log::error("Background script execution failed: " . $e->getMessage());
            return null;
        }
    }
}
